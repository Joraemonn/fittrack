# FitTrack CV — API Integration Spec

> **Purpose:** Spec for integrating the FitTrack AI food classifier into the FitTrack website's Meals section. The classifier is a separate Python service (FastAPI + PyTorch ResNet-18) that runs locally on `http://localhost:8000`. This document tells Codex (or any developer) exactly how to call it, what it returns, and how to wire it into the existing Meals page.

---

## 1. The Big Picture

The FitTrack website (PHP + MySQL on XAMPP) gives the user two ways to log a meal:

1. **Manual entry** — type food name, calories, etc.
2. **🆕 Scan a meal** — take/upload a photo → AI predicts the food → save it

This spec covers **option 2 only**. The flow is:

```
[User on Meals page]
        ↓ chooses "Scan Meal"
[Camera opens OR file picker]
        ↓ captures/selects image
[JavaScript sends image to API]
        ↓ POST http://localhost:8000/predict (multipart/form-data)
[FastAPI returns JSON with food + nutrition + confidence]
        ↓ JavaScript displays the result
[User confirms → save to MySQL]
        ↓ AJAX call to a PHP endpoint (e.g. save_meal.php)
[PHP inserts row into meals table]
```

**Key architectural note:** The Python API is a **separate process** running on port 8000. It is *not* part of the PHP/Apache stack. To run it, the user opens a terminal, activates the venv, and runs:

```bash
uvicorn api:app --reload
```

For local development, both XAMPP (Apache) and Uvicorn run side-by-side on the same machine. CORS is already configured on the API side to allow requests from `http://localhost`.

---

## 2. API Reference

### Base URL

```
http://localhost:8000
```

### Endpoints

| Method | Path        | Purpose                                |
|--------|-------------|----------------------------------------|
| GET    | `/`         | Health check, returns API metadata     |
| GET    | `/health`   | Simple `{"status": "healthy"}`         |
| GET    | `/docs`     | Auto-generated interactive Swagger UI  |
| POST   | `/predict`  | **Main endpoint** — image → prediction |

### `POST /predict` — full contract

**Request:**

- Method: `POST`
- URL: `http://localhost:8000/predict`
- Content-Type: `multipart/form-data`
- Body: a single form field named `file` containing the image (JPG, PNG, WebP, etc.)

**curl example:**

```bash
curl -X POST 'http://localhost:8000/predict' \
  -F 'file=@meal.jpg;type=image/jpeg'
```

**JavaScript example (fetch):**

```javascript
const formData = new FormData();
formData.append('file', imageBlob);  // imageBlob can come from <input type="file"> or canvas

const response = await fetch('http://localhost:8000/predict', {
  method: 'POST',
  body: formData
});

const data = await response.json();
console.log(data.prediction.food_display);  // e.g. "Chicken Rice"
```

**Success Response (HTTP 200):**

```json
{
  "success": true,
  "prediction": {
    "food": "chicken_rice",
    "food_display": "Chicken Rice",
    "confidence": 99.94,
    "is_singapore_food": true
  },
  "nutrition": {
    "calories": 175,
    "protein": 11,
    "carbs": 20,
    "fats": 6,
    "per": "100g",
    "source": "Siena Health + Arise (607 kcal per 382g plate)",
    "fdc_id": null,
    "description": "Hainanese chicken rice (poached/roasted chicken with rice cooked in chicken fat)"
  },
  "top_5_alternatives": [
    { "food": "chicken_rice",  "confidence": 99.94, "is_singapore_food": true  },
    { "food": "porridge",      "confidence": 0.06,  "is_singapore_food": true  },
    { "food": "fried_rice",    "confidence": 0,     "is_singapore_food": false },
    { "food": "grilled_salmon","confidence": 0,     "is_singapore_food": false },
    { "food": "nasi_lemak",    "confidence": 0,     "is_singapore_food": true  }
  ],
  "filename": "meal.jpg"
}
```

**Field reference:**

| Field | Type | Notes |
|-------|------|-------|
| `success` | bool | Always `true` on 200 |
| `prediction.food` | string | Machine-readable label (snake_case). Use as the primary key when saving to DB. |
| `prediction.food_display` | string | Human-readable name (`"Chicken Rice"`). Use for UI display. |
| `prediction.confidence` | number | 0.0 – 100.0, rounded to 2 decimals |
| `prediction.is_singapore_food` | bool | `true` for the 20 local SG foods, `false` for the 101 Food-101 categories. Use this to show a 🇸🇬 flag if desired. |
| `nutrition` | object \| null | `null` if no nutrition entry exists for this food (should not happen with current dataset, but check defensively) |
| `nutrition.calories` | number | kcal per 100g |
| `nutrition.protein` | number | grams per 100g |
| `nutrition.carbs` | number | grams per 100g |
| `nutrition.fats` | number | grams per 100g |
| `nutrition.per` | string | Always `"100g"` |
| `nutrition.source` | string | Provenance string (USDA, HealthHub, etc.) |
| `nutrition.fdc_id` | number \| null | USDA FoodData Central ID if available, else `null` |
| `nutrition.description` | string | Short description |
| `top_5_alternatives` | array | Top 5 model predictions, sorted by confidence |
| `filename` | string | Original filename uploaded |

**Error responses:**

| HTTP | Body | When |
|------|------|------|
| 400 | `{"detail": "File must be an image. Got: text/plain"}` | Non-image uploaded |
| 400 | `{"detail": "Could not read image: ..."}` | Corrupt/unreadable image |
| 422 | `{"detail": [...]}` | Missing `file` field |
| 500 | `{"detail": "..."}` | Server-side error |

---

## 3. UI / UX Requirements

### Where it lives

In the **existing Meals section**. Add a new "Scan Meal" entry point (button, card, or tab — whatever fits the existing layout). When clicked, it opens the scan flow.

### Two capture modes (toggle)

The user can choose between:

**Mode A — Camera (mobile-feel)**
- Use the browser's `getUserMedia()` API to open the camera
- Show a live preview
- "Capture" button takes a still frame to a `<canvas>`, then converts to a Blob for upload
- Prefer rear camera on mobile: `{ video: { facingMode: 'environment' } }`

**Mode B — File upload (desktop-friendly)**
- Standard `<input type="file" accept="image/*">`
- Show a preview of the selected image before uploading

### After capture/selection

1. Show a preview of the chosen/captured image
2. Show an "Analyze Meal" button
3. On click: send to API, show a loading spinner
4. Display results when ready (see below)
5. Show a "Confirm & Save" button to commit to DB
6. (Optional) "Retake / Choose different" button to redo

### Result display

Show, at minimum:

- **Food name** (`food_display`) prominently
- **🇸🇬 flag** if `is_singapore_food === true`
- **Confidence** (e.g. "AI confidence: 99.94%")
- **Nutrition card**: calories / protein / carbs / fats per 100g
- **Source citation** (small text, e.g. "Nutrition data: USDA FoodData Central")

Nice-to-haves:

- A "Not quite right?" link revealing the top 5 alternatives, so the user can pick a different one
- A serving size input (e.g. grams or "1 plate") that recalculates totals (e.g. if user eats 250g, multiply nutrition × 2.5)

### Confidence handling

The model is highly confident on its training distribution but can be wrong on edge cases. Suggested thresholds:

| Confidence | UI suggestion |
|-----------|---------------|
| ≥ 80% | Show prediction with confidence, default to it |
| 50–80% | Show "Best guess: X. Top alternatives: ..." prominently |
| < 50% | Show "Couldn't identify confidently. Please pick from list or enter manually." |

### Styling

**Match the existing FitTrack style** — same color palette, typography, spacing, button styles, card patterns. Do not introduce a new design language for this feature; it should feel native to the rest of the Meals section.

---

## 4. Database Integration

### What to save

The user's existing `meals` table (or equivalent — Codex should inspect what's already there) needs to support scanned meals. Suggested columns to ensure exist:

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT, PK, auto-increment | |
| `user_id` | INT, FK | Existing |
| `food_name` | VARCHAR(100) | Use `prediction.food` (snake_case) so it links to nutrition data later |
| `food_display` | VARCHAR(100) | Use `prediction.food_display` for showing in meal logs |
| `calories` | DECIMAL(7,2) | Stored per the serving the user actually ate (not per 100g) |
| `protein` | DECIMAL(6,2) | Same |
| `carbs` | DECIMAL(6,2) | Same |
| `fats` | DECIMAL(6,2) | Same |
| `serving_grams` | DECIMAL(6,1), NULL | If user entered a serving size, store it. NULL = "1 serving, no specific weight" |
| `source` | ENUM('manual', 'scanned') | So you can distinguish entries in the meal log |
| `ai_confidence` | DECIMAL(5,2), NULL | Useful for auditing low-confidence saves |
| `image_path` | VARCHAR(255), NULL | Optional — see "image storage" below |
| `created_at` | TIMESTAMP | |

**Codex should check the actual existing `meals` schema first** and adapt:
- If columns already exist, use them
- If columns are missing, propose an `ALTER TABLE` migration
- Use sensible defaults (e.g., `source = 'manual'` for all existing rows)

### Image storage — decide on the spot

Two options. Codex picks based on existing FitTrack conventions:

**Option A: Save the image**
- Save to `htdocs/fittrack/uploads/meals/<user_id>/<timestamp>.jpg`
- Store the path in `meals.image_path`
- Pros: user can review what they scanned, can rerun analysis if needed
- Cons: disk space, need cleanup logic eventually

**Option B: Don't save the image**
- Just store the prediction data
- Pros: simpler, no disk management
- Cons: can't show the photo in the meal log later

**Recommendation:** If FitTrack already saves any user-uploaded media (e.g., profile pics), follow the same pattern. If not, **start with Option B** for simplicity — saving images can be added later without breaking anything.

### Suggested save flow

1. Frontend gets prediction from `/predict`
2. User clicks "Confirm & Save"
3. JavaScript POSTs to a new PHP endpoint: `save_scanned_meal.php`
4. PHP endpoint:
   - Validates user is logged in (existing session check)
   - Sanitizes inputs
   - Inserts row into `meals` with `source = 'scanned'`
   - Returns JSON `{"success": true, "meal_id": 123}`
5. Frontend shows success toast, redirects to meal log or stays on scan page

---

## 5. Error Handling

Handle these scenarios gracefully:

| Scenario | UX |
|----------|----|
| API is offline (Uvicorn not running) | Show "AI service unavailable. Please try again or enter manually." Provide a manual entry fallback. |
| Camera permission denied | Hide camera mode, fall back to upload-only. |
| Image upload fails (network) | "Couldn't reach AI service. Check your connection." Retry button. |
| 400 error (bad image) | "We couldn't read that image. Try a different photo." |
| 500 error | "Something went wrong on our end. Please try again." |
| Low confidence (< 50%) | Show alternatives, don't auto-save |
| `nutrition === null` (no nutrition data) | Save prediction without nutrition; prompt user to enter macros manually |

---

## 6. Security Notes

- The API runs **locally** during development. For production, you'd deploy the API behind authentication (token-based) and HTTPS.
- Don't expose the API URL in client-side code that goes to production without auth.
- For now (dev), the open CORS policy + local network is fine.
- File uploads should be validated: max size (e.g., 10MB), accept only `image/*` MIME types on the PHP side too (not just trust the client).

---

## 7. Testing Checklist

Before integrating, verify:

- [ ] API is running: `curl http://localhost:8000/health` returns `{"status":"healthy"}`
- [ ] API can predict: upload `test.jpg` via `/docs` page, see correct prediction
- [ ] CORS works: open `test_scan.html` (provided) and successfully predict
- [ ] After integration: scan a meal → see prediction → save → confirms in DB

---

## 8. Quick Reference — Working JavaScript Snippet

A minimal, copy-pasteable example that handles both camera and upload:

```javascript
// ─── File upload mode ───
document.getElementById('fileInput').addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  // Show preview
  document.getElementById('preview').src = URL.createObjectURL(file);

  // Send to API
  const result = await predictMeal(file);
  displayResult(result);
});

// ─── Camera mode ───
async function startCamera() {
  const stream = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: 'environment' }  // rear camera on mobile
  });
  document.getElementById('video').srcObject = stream;
}

function captureFromCamera() {
  const video = document.getElementById('video');
  const canvas = document.getElementById('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);

  canvas.toBlob(async (blob) => {
    const result = await predictMeal(blob);
    displayResult(result);
  }, 'image/jpeg', 0.9);
}

// ─── Shared API call ───
async function predictMeal(fileOrBlob) {
  const formData = new FormData();
  formData.append('file', fileOrBlob, 'meal.jpg');

  const response = await fetch('http://localhost:8000/predict', {
    method: 'POST',
    body: formData
  });

  if (!response.ok) {
    throw new Error(`API error: ${response.status}`);
  }

  return await response.json();
}

// ─── Display ───
function displayResult(data) {
  const { prediction, nutrition } = data;
  document.getElementById('foodName').textContent =
    prediction.food_display + (prediction.is_singapore_food ? ' 🇸🇬' : '');
  document.getElementById('confidence').textContent =
    `AI confidence: ${prediction.confidence}%`;

  if (nutrition) {
    document.getElementById('calories').textContent = nutrition.calories;
    document.getElementById('protein').textContent  = nutrition.protein;
    document.getElementById('carbs').textContent    = nutrition.carbs;
    document.getElementById('fats').textContent     = nutrition.fats;
  }
}
```

---

## 9. Reference — All 121 Supported Foods

The classifier recognizes:

**Food-101 (101 international categories):**
apple_pie, baby_back_ribs, baklava, beef_carpaccio, beef_tartare, beet_salad, beignets, bibimbap, bread_pudding, breakfast_burrito, bruschetta, caesar_salad, cannoli, caprese_salad, carrot_cake, ceviche, cheesecake, cheese_plate, chicken_curry, chicken_quesadilla, chicken_wings, chocolate_cake, chocolate_mousse, churros, clam_chowder, club_sandwich, crab_cakes, creme_brulee, croque_madame, cup_cakes, deviled_eggs, donuts, dumplings, edamame, eggs_benedict, escargots, falafel, filet_mignon, fish_and_chips, foie_gras, french_fries, french_onion_soup, french_toast, fried_calamari, fried_rice, frozen_yogurt, garlic_bread, gnocchi, greek_salad, grilled_cheese_sandwich, grilled_salmon, guacamole, gyoza, hamburger, hot_and_sour_soup, hot_dog, huevos_rancheros, hummus, ice_cream, lasagna, lobster_bisque, lobster_roll_sandwich, macaroni_and_cheese, macarons, miso_soup, mussels, nachos, omelette, onion_rings, oysters, pad_thai, paella, pancakes, panna_cotta, peking_duck, pho, pizza, pork_chop, poutine, prime_rib, pulled_pork_sandwich, ramen, ravioli, red_velvet_cake, risotto, samosa, sashimi, scallops, seaweed_salad, shrimp_and_grits, spaghetti_bolognese, spaghetti_carbonara, spring_rolls, steak, strawberry_shortcake, sushi, tacos, takoyaki, tiramisu, tuna_tartare, waffles

**Singapore foods (20 local categories):**
bak_kut_teh, char_kway_teow, chicken_rice, chilli_crab, curry_puff, fried_carrot_cake, hokkien_mee, ice_kacang, kaya_toast, laksa, lor_mee, mee_siam, nasi_lemak, popiah, porridge, roti_prata, satay, sliced_fish_soup, tau_suan, yong_tau_foo

If the user's meal isn't in these 121 categories, the model will predict the closest one — show top 5 alternatives so the user can override or fall back to manual entry.

---

## 10. Summary for Codex

**What to do, in order:**

1. **Inspect the existing FitTrack codebase** — find the Meals page, the existing meal entry form, the DB schema, the auth/session pattern.
2. **Add an entry point** in the Meals section for "Scan Meal" (button or card matching existing style).
3. **Build the scan UI** with camera + upload toggle. Match existing FitTrack styling.
4. **Wire up the prediction call** using the JavaScript snippet above. Handle the error scenarios listed.
5. **Build `save_scanned_meal.php`** to receive the confirmed result and insert into the `meals` table. Migrate the table if needed.
6. **Test end-to-end** using the provided `test_scan.html` to verify the API works before doing real integration.

**Files to expect from this spec:**
- `API_INTEGRATION_SPEC.md` (this document)
- `test_scan.html` — standalone test page to verify the API works

The API itself is already built and tested. It will be running on `http://localhost:8000` whenever the user has Uvicorn started in the `fittrackCV` project.
