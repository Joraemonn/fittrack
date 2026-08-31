document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navMenu = document.querySelector('[data-nav-menu]');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
        });
    }

    const settingsTabs = document.querySelectorAll('[data-settings-tab]');
    const settingsPanels = document.querySelectorAll('[data-settings-panel]');

    settingsTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.settingsTab;

            settingsTabs.forEach((item) => {
                item.classList.toggle('active', item === tab);
            });

            settingsPanels.forEach((panel) => {
                panel.hidden = panel.dataset.settingsPanel !== target;
            });
        });
    });

    const manualMealToggle = document.querySelector('[data-manual-meal-toggle]');
    const manualMealForm = document.querySelector('[data-manual-meal-form]');

    const setManualMealForm = (open) => {
        if (!manualMealToggle || !manualMealForm) {
            return;
        }

        manualMealForm.classList.toggle('open', open);
        manualMealToggle.classList.toggle('active', open);
        manualMealToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            window.setTimeout(() => {
                manualMealForm.querySelector('input, select, textarea')?.focus();
            }, 180);
        }
    };

    if (manualMealToggle && manualMealForm) {
        manualMealToggle.addEventListener('click', () => {
            setManualMealForm(!manualMealForm.classList.contains('open'));
        });
    }

    const profileForm = document.querySelector('[data-profile-form]');

    if (profileForm) {
        const measurementSelect = profileForm.querySelector('#measurement_units');
        const profileValueUnits = profileForm.querySelector('[data-profile-value-units]');
        const heightInput = profileForm.querySelector('[data-height-input]');
        const weightInput = profileForm.querySelector('[data-weight-input]');
        const heightLabel = profileForm.querySelector('[data-height-label]');
        const weightLabel = profileForm.querySelector('[data-weight-label]');
        const profileImageInput = document.getElementById('profile_image');
        const avatarUpload = document.querySelector('.avatar-upload');

        const heightUnit = (units) => units === 'imperial' ? 'in' : 'cm';
        const weightUnit = (units) => units === 'metric' ? 'kg' : 'lb';

        const heightToCm = (height, units) => units === 'imperial' ? height * 2.54 : height;
        const heightFromCm = (height, units) => units === 'imperial' ? height / 2.54 : height;
        const weightToKg = (weight, units) => units === 'metric' ? weight : weight / 2.20462;
        const weightFromKg = (weight, units) => units === 'metric' ? weight : weight * 2.20462;

        const formatMeasurement = (value) => {
            if (!Number.isFinite(value)) {
                return '';
            }

            return (Math.round(value * 100) / 100).toFixed(2);
        };

        const convertProfileMeasurements = (nextUnits) => {
            const currentUnits = profileValueUnits?.value || nextUnits;

            if (heightInput && heightInput.value !== '') {
                const height = Number.parseFloat(heightInput.value);

                if (Number.isFinite(height)) {
                    heightInput.value = formatMeasurement(heightFromCm(heightToCm(height, currentUnits), nextUnits));
                }
            }

            if (weightInput && weightInput.value !== '') {
                const weight = Number.parseFloat(weightInput.value);

                if (Number.isFinite(weight)) {
                    weightInput.value = formatMeasurement(weightFromKg(weightToKg(weight, currentUnits), nextUnits));
                }
            }

            if (heightLabel) {
                heightLabel.textContent = `Height (${heightUnit(nextUnits)})`;
            }

            if (weightLabel) {
                weightLabel.textContent = `Weight (${weightUnit(nextUnits)})`;
            }

            if (profileValueUnits) {
                profileValueUnits.value = nextUnits;
            }
        };

        if (measurementSelect) {
            convertProfileMeasurements(measurementSelect.value);
            measurementSelect.addEventListener('change', () => convertProfileMeasurements(measurementSelect.value));
        }

        if (profileImageInput && avatarUpload) {
            profileImageInput.addEventListener('change', () => {
                const file = profileImageInput.files?.[0];

                if (!file || !file.type.startsWith('image/')) {
                    return;
                }

                const previewUrl = URL.createObjectURL(file);
                const existingAvatar = avatarUpload.querySelector('.avatar');
                const image = document.createElement('img');
                image.className = 'avatar';
                image.alt = 'Profile photo';
                image.src = previewUrl;
                existingAvatar?.replaceWith(image);
            });
        }
    }

    const exerciseNameInput = document.querySelector('[data-exercise-name]');
    const muscleGroupInput = document.querySelector('[data-muscle-group]');
    const exerciseDataElement = document.getElementById('exercise-catalog-data');
    const exercisePreview = document.querySelector('[data-exercise-preview]');
    const exercisePreviewName = document.querySelector('[data-exercise-preview-name]');
    const exercisePreviewMuscle = document.querySelector('[data-exercise-preview-muscle]');
    const muscleGroupField = document.querySelector('[data-muscle-group-field]');
    const exerciseModal = document.querySelector('[data-exercise-modal]');
    const exerciseSearch = document.querySelector('[data-exercise-search]');
    const exerciseResults = document.querySelector('[data-exercise-results]');
    const exerciseCount = document.querySelector('[data-exercise-count]');
    const exerciseMuscleSelect = document.querySelector('[data-exercise-muscle-select]');
    const exerciseMuscleToggle = document.querySelector('[data-exercise-muscle-toggle]');
    const exerciseMuscleMenu = document.querySelector('[data-exercise-muscle-menu]');
    const exerciseMuscleLabel = document.querySelector('[data-exercise-muscle-label]');
    let exerciseMuscleOptions = document.querySelectorAll('[data-exercise-muscle-option]');
    const exerciseOpenButtons = document.querySelectorAll('[data-exercise-open]');
    const exerciseCloseButtons = document.querySelectorAll('[data-exercise-close]');
    const exerciseClearButton = document.querySelector('[data-exercise-clear]');
    const customExerciseModal = document.querySelector('[data-custom-exercise-modal]');
    const customExerciseOpenButton = document.querySelector('[data-custom-exercise-open]');
    const customExerciseCloseButtons = document.querySelectorAll('[data-custom-exercise-close]');
    const customExerciseForm = document.querySelector('[data-custom-exercise-form]');
    const customExerciseName = document.querySelector('[data-custom-exercise-name]');
    const customExerciseMuscle = document.querySelector('[data-custom-exercise-muscle]');
    const customExerciseMessage = document.querySelector('[data-custom-exercise-message]');
    const customMuscleModal = document.querySelector('[data-custom-muscle-modal]');
    const customMuscleOpenButton = document.querySelector('[data-custom-muscle-open]');
    const customMuscleCloseButtons = document.querySelectorAll('[data-custom-muscle-close]');
    const customMuscleMenu = document.querySelector('[data-custom-muscle-menu]');
    const customMuscleLabel = document.querySelector('[data-custom-muscle-label]');
    let customMuscleOptions = document.querySelectorAll('[data-custom-muscle-option]');
    const customMuscleOtherField = document.querySelector('[data-custom-muscle-other-field]');
    const customMuscleOther = document.querySelector('[data-custom-muscle-other]');
    const workoutRepsLabel = document.querySelector('[data-workout-reps-label]');
    const workoutRepsInput = document.querySelector('[data-workout-reps-input]');
    const workoutDurationField = document.querySelector('[data-workout-duration-field]');
    const workoutDurationTrigger = document.querySelector('[data-workout-duration-trigger]');
    const workoutDurationLabel = document.querySelector('[data-workout-duration-label]');
    const workoutDurationPicker = document.querySelector('[data-workout-duration-picker]');
    const workoutDurationHours = document.querySelector('[data-workout-duration-hours]');
    const workoutDurationMinutes = document.querySelector('[data-workout-duration-minutes]');
    const workoutDurationSeconds = document.querySelector('[data-workout-duration-seconds]');

    if (exerciseNameInput && muscleGroupInput && exerciseDataElement) {
        const exercises = JSON.parse(exerciseDataElement.textContent || '[]');
        const exerciseApiUrl = window.FITTRACK_EXERCISE_API || 'api/exercises.php';
        const customExerciseApiUrl = window.FITTRACK_CUSTOM_EXERCISE_API || 'api/custom_exercises.php';
        let allExercises = [];
        let exercisesLoaded = false;
        let exercisesLoadingPromise = null;
        let activeMuscle = '';

        const normalizeKey = (value) => {
            return (value || '')
                .toString()
                .trim()
                .toLowerCase()
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ');
        };

        const isTimedExercise = (value) => {
            return /\bplank\b/i.test(value || '');
        };

        const formatDuration = (totalSeconds) => {
            const seconds = Number.parseInt(totalSeconds, 10);

            if (!Number.isFinite(seconds) || seconds <= 0) {
                return 'Select time';
            }

            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;
            const parts = [];

            if (hours) {
                parts.push(`${hours}h`);
            }

            if (minutes) {
                parts.push(`${minutes}m`);
            }

            if (remainingSeconds || parts.length === 0) {
                parts.push(`${remainingSeconds}s`);
            }

            return parts.join(' ');
        };

        const durationParts = () => {
            const seconds = Number.parseInt(workoutRepsInput?.value || '0', 10);

            if (!Number.isFinite(seconds) || seconds <= 0) {
                return { hours: 0, minutes: 1, seconds: 0 };
            }

            return {
                hours: Math.floor(seconds / 3600),
                minutes: Math.floor((seconds % 3600) / 60),
                seconds: seconds % 60,
            };
        };

        const renderDurationOptions = () => {
            if (!workoutDurationHours || !workoutDurationMinutes || !workoutDurationSeconds || !workoutRepsInput) {
                return;
            }

            const current = durationParts();
            const createOption = (value, unit, selected) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `${value}${unit}`;
                button.classList.toggle('active', selected);
                button.addEventListener('click', () => {
                    const next = durationParts();
                    next[unit === 'h' ? 'hours' : unit === 'm' ? 'minutes' : 'seconds'] = value;
                    const totalSeconds = (next.hours * 3600) + (next.minutes * 60) + next.seconds;
                    workoutRepsInput.value = totalSeconds > 0 ? String(totalSeconds) : '';

                    if (workoutDurationLabel) {
                        workoutDurationLabel.textContent = formatDuration(workoutRepsInput.value);
                    }

                    renderDurationOptions();
                });

                return button;
            };

            workoutDurationHours.innerHTML = '';
            workoutDurationMinutes.innerHTML = '';
            workoutDurationSeconds.innerHTML = '';

            for (let hour = 0; hour <= 5; hour += 1) {
                workoutDurationHours.append(createOption(hour, 'h', current.hours === hour));
            }

            for (let minute = 0; minute <= 59; minute += 1) {
                workoutDurationMinutes.append(createOption(minute, 'm', current.minutes === minute));
            }

            for (let second = 0; second <= 59; second += 1) {
                workoutDurationSeconds.append(createOption(second, 's', current.seconds === second));
            }
        };

        const closeDurationPicker = () => {
            if (workoutDurationPicker) {
                workoutDurationPicker.hidden = true;
            }
        };

        const updateRepMetricMode = (exerciseName) => {
            const timed = isTimedExercise(exerciseName);

            if (workoutRepsLabel) {
                workoutRepsLabel.textContent = timed ? 'Time' : 'Reps';
            }

            if (workoutRepsInput) {
                workoutRepsInput.type = timed ? 'hidden' : 'number';
                workoutRepsInput.placeholder = '';
                workoutRepsInput.setAttribute('aria-label', timed ? 'Time' : 'Reps');
            }

            if (workoutDurationField) {
                workoutDurationField.hidden = !timed;
            }

            if (!timed) {
                closeDurationPicker();
            }

            if (workoutDurationLabel) {
                workoutDurationLabel.textContent = formatDuration(workoutRepsInput?.value);
            }
        };

        const showExerciseMatch = (match) => {
            if (!match) {
                if (exercisePreview) {
                    exercisePreview.hidden = true;
                }

                if (muscleGroupField && !muscleGroupInput.value.trim()) {
                    muscleGroupField.hidden = true;
                }

                return;
            }

            if (exercisePreview && exercisePreviewName && exercisePreviewMuscle) {
                exercisePreviewName.textContent = match.name;
                exercisePreviewMuscle.textContent = match.muscle_group || readableMuscle(match.muscle);
                exercisePreview.hidden = false;
            }

            if (muscleGroupField) {
                muscleGroupField.hidden = false;
            }
        };

        const readableMuscle = (muscle) => {
            return (muscle || '')
                .toString()
                .replace(/[_-]/g, ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        };

        const normalizeExercise = (exercise) => {
            const targetMuscles = Array.isArray(exercise.targetMuscles) ? exercise.targetMuscles : [];
            const secondaryMuscles = Array.isArray(exercise.secondaryMuscles) ? exercise.secondaryMuscles : [];
            const muscleGroup = exercise.muscleGroup || exercise.muscle_group || readableMuscle(targetMuscles[0] || exercise.muscle);
            const name = cleanExerciseName(exercise.name || '');

            return {
                name,
                muscle: exercise.muscle || muscleGroup.toLowerCase().replace(/\s+/g, '_'),
                muscleGroup,
                muscle_group: muscleGroup,
                bodyPart: exercise.bodyPart || '',
                targetMuscles,
                secondaryMuscles,
                type: exercise.type || '',
                equipment: exercise.equipment || exercise.equipments || '',
                equipments: exercise.equipments || exercise.equipment || '',
                difficulty: exercise.difficulty || '',
                instructions: exercise.instructions || '',
                safety_info: exercise.safety_info || '',
                source: exercise.source || '',
            };
        };

        const shouldExcludeExerciseName = (name) => {
            const exerciseName = name || '';

            return /\d/.test(exerciseName)
                || exerciseName.includes('/')
                || /^\s*hm\b/i.test(exerciseName)
                || /\bgood\s+morning\b/i.test(exerciseName)
                || /\bpartner\b/i.test(exerciseName)
                || /\bsmr\b/i.test(exerciseName)
                || /\bstretch\b/i.test(exerciseName)
                || /(?:^|[\s-])to(?:[\s-]|$)/i.test(exerciseName);
        };

        const cleanExerciseName = (name) => {
            return (name || '')
                .replace(/\b(?:FYR|AM)\b\s*/gi, '')
                .replace(/\s*[-–—]\s*(?:Rope\s+Attachment|Cable\s+Attachment|Bar\s+Attachment|V-Bar\s+Attachment|Straight\s+Bar\s+Attachment)\s*$/i, '')
                .replace(/\bRope\s+Triceps\s+Pushdown\b/i, 'Triceps Pushdown')
                .replace(/\s*[-–—]\s*(?:Gethin\s+Variation|Variation)\s*$/i, '')
                .replace(/\s*\((?:Pull\s+Through|Rope\s+Attachment|Cable\s+Attachment|Barbell|Dumbbell|Machine|Smith\s+Machine|Cable|Band)\)\s*$/i, '')
                .replace(/\s+/g, ' ')
                .replace(/^[\s\-–—]+|[\s\-–—]+$/g, '')
                .toLowerCase()
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        };

        const dedupeExercises = (items) => {
            const unique = new Map();

            items.map(normalizeExercise).forEach((exercise) => {
                if (shouldExcludeExerciseName(exercise.name)) {
                    return;
                }

                const key = normalizeKey(exercise.name);

                if (key && !unique.has(key)) {
                    unique.set(key, exercise);
                }
            });

            return [...unique.values()].sort((a, b) => a.name.localeCompare(b.name));
        };

        const loadCustomExercises = async () => {
            try {
                const response = await fetch(customExerciseApiUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    return [];
                }

                const payload = await response.json();
                return Array.isArray(payload.exercises) ? payload.exercises : [];
            } catch (error) {
                console.log('[FitTrack exercise picker] custom exercise load failed:', error);
                return [];
            }
        };

        const bindMuscleOptions = () => {
            exerciseMuscleOptions = document.querySelectorAll('[data-exercise-muscle-option]');

            exerciseMuscleOptions.forEach((option) => {
                if (option.dataset.bound === 'true') {
                    return;
                }

                option.dataset.bound = 'true';
                option.addEventListener('click', () => {
                    activeMuscle = option.value;

                    exerciseMuscleOptions.forEach((item) => {
                        item.classList.toggle('active', item === option);
                    });

                    if (exerciseMuscleLabel) {
                        exerciseMuscleLabel.textContent = option.textContent || 'All Muscles';
                    }

                    closeMuscleMenu();
                    loadExerciseResults();
                });
            });
        };

        const closeCustomMuscleMenu = () => {
            if (!customMuscleModal) {
                return;
            }

            customMuscleModal.hidden = true;
        };

        const openCustomMuscleMenu = () => {
            if (!customMuscleModal) {
                return;
            }

            customMuscleModal.hidden = false;
            document.body.classList.add('modal-open');
        };

        const setCustomMuscle = (value, label) => {
            const isOther = value === '__other';

            if (customExerciseMuscle) {
                customExerciseMuscle.value = isOther ? '' : value;
            }

            if (customMuscleLabel) {
                customMuscleLabel.textContent = label || 'Choose Muscle Group';
            }

            if (customMuscleOtherField && customMuscleOther) {
                customMuscleOtherField.hidden = !isOther;
                customMuscleOther.required = isOther;

                if (!isOther) {
                    customMuscleOther.value = '';
                }
            }

            customMuscleOptions.forEach((option) => {
                option.classList.toggle('active', option.value === value);
            });

            closeCustomMuscleMenu();

            if (isOther && customMuscleOther) {
                window.setTimeout(() => customMuscleOther.focus(), 0);
            }
        };

        const bindCustomMuscleOptions = () => {
            customMuscleOptions = document.querySelectorAll('[data-custom-muscle-option]');

            customMuscleOptions.forEach((option) => {
                if (option.dataset.bound === 'true') {
                    return;
                }

                option.dataset.bound = 'true';
                option.addEventListener('click', () => {
                    setCustomMuscle(option.value, option.textContent || 'Choose Muscle Group');
                });
            });
        };

        const refreshCustomMuscleOptions = () => {
            if (!customMuscleMenu) {
                return;
            }

            const otherOption = customMuscleMenu.querySelector('[data-custom-muscle-option][value="__other"]');
            const existing = new Set(
                Array.from(customMuscleMenu.querySelectorAll('[data-custom-muscle-option]')).map((option) => normalizeKey(option.value))
            );
            const muscles = [...new Map(allExercises.map((exercise) => {
                const normalized = normalizeExercise(exercise);
                return [normalizeKey(normalized.muscle_group), normalized.muscle_group];
            })).values()].sort((a, b) => a.localeCompare(b));

            muscles.forEach((muscle) => {
                const key = normalizeKey(muscle);

                if (!key || existing.has(key)) {
                    return;
                }

                const option = document.createElement('button');
                option.type = 'button';
                option.value = muscle;
                option.dataset.customMuscleOption = '';
                option.textContent = muscle;

                if (otherOption) {
                    customMuscleMenu.insertBefore(option, otherOption);
                } else {
                    customMuscleMenu.append(option);
                }

                existing.add(key);
            });

            bindCustomMuscleOptions();
        };

        const refreshMuscleOptions = () => {
            if (!exerciseMuscleMenu) {
                return;
            }

            const existing = new Set(
                Array.from(exerciseMuscleMenu.querySelectorAll('[data-exercise-muscle-option]')).map((option) => normalizeKey(option.value))
            );
            const muscles = [...new Map(allExercises.map((exercise) => {
                const normalized = normalizeExercise(exercise);
                return [normalizeKey(normalized.muscle_group), normalized.muscle_group];
            })).values()].sort((a, b) => a.localeCompare(b));

            muscles.forEach((muscle) => {
                const key = normalizeKey(muscle);

                if (!key || existing.has(key)) {
                    return;
                }

                const option = document.createElement('button');
                option.type = 'button';
                option.value = muscle;
                option.dataset.exerciseMuscleOption = '';
                option.textContent = muscle;
                exerciseMuscleMenu.append(option);
                existing.add(key);
            });

            bindMuscleOptions();
            refreshCustomMuscleOptions();
        };

        const findExactExercise = (value) => {
            const search = normalizeKey(value);
            return allExercises.find((exercise) => normalizeKey(exercise.name) === search)
                || exercises.map(normalizeExercise).find((exercise) => {
                    return !shouldExcludeExerciseName(exercise.name) && normalizeKey(exercise.name) === search;
                });
        };

        const updateExerciseMatch = () => {
            const match = findExactExercise(exerciseNameInput.value);

            if (match) {
                exerciseNameInput.value = match.name;
                muscleGroupInput.value = match.muscle_group;
            }

            showExerciseMatch(match);
            updateRepMetricMode(match?.name || exerciseNameInput.value);
        };

        const closeExerciseModal = () => {
            if (!exerciseModal) {
                return;
            }

            closeMuscleMenu();
            closeCustomExerciseModal();
            exerciseModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        const closeMuscleMenu = () => {
            if (!exerciseMuscleMenu || !exerciseMuscleToggle) {
                return;
            }

            exerciseMuscleMenu.hidden = true;
            exerciseMuscleToggle.setAttribute('aria-expanded', 'false');
        };

        const toggleMuscleMenu = () => {
            if (!exerciseMuscleMenu || !exerciseMuscleToggle) {
                return;
            }

            const willOpen = exerciseMuscleMenu.hidden;
            exerciseMuscleMenu.hidden = !willOpen;
            exerciseMuscleToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        };

        const selectExercise = (exercise) => {
            const normalizedExercise = normalizeExercise(exercise);
            exerciseNameInput.value = normalizedExercise.name;
            muscleGroupInput.value = normalizedExercise.muscle_group;
            showExerciseMatch(normalizedExercise);
            updateRepMetricMode(normalizedExercise.name);
            closeExerciseModal();
        };

        const fallbackExercises = () => {
            return dedupeExercises(exercises);
        };

        const loadAllExercises = async () => {
            if (exercisesLoaded) {
                return allExercises;
            }

            if (exercisesLoadingPromise) {
                return exercisesLoadingPromise;
            }

            exercisesLoadingPromise = (async () => {
                try {
                    const response = await fetch(exerciseApiUrl, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load exercises.');
                    }

                    const payload = await response.json();
                    const apiExercises = Array.isArray(payload.exercises) ? payload.exercises : [];
                    const customExercises = await loadCustomExercises();
                    allExercises = dedupeExercises([...customExercises, ...apiExercises]);
                    refreshMuscleOptions();
                    exercisesLoaded = true;
                    console.log('[FitTrack exercise picker] total exercises loaded:', apiExercises.length);
                    console.log('[FitTrack exercise picker] total unique exercises after deduplication:', allExercises.length);
                } catch (error) {
                    const customExercises = await loadCustomExercises();
                    allExercises = dedupeExercises([...customExercises, ...fallbackExercises()]);
                    refreshMuscleOptions();
                    exercisesLoaded = true;
                    console.log('[FitTrack exercise picker] API load failed; using local fallback:', error);
                    console.log('[FitTrack exercise picker] total unique exercises after deduplication:', allExercises.length);
                }

                return allExercises;
            })();

            return exercisesLoadingPromise;
        };

        const getFilteredExercises = () => {
            const query = normalizeKey(exerciseSearch?.value || '');
            const muscleFilter = normalizeKey(activeMuscle);
            const filtered = allExercises.filter((exercise) => {
                const exerciseName = normalizeKey(exercise.name);
                const exerciseMuscle = normalizeKey(exercise.muscle);
                const exerciseMuscleGroup = normalizeKey(exercise.muscle_group);
                const matchesText = !query || exerciseName.includes(query);
                const matchesMuscle = !muscleFilter || exerciseMuscle === muscleFilter || exerciseMuscleGroup === muscleFilter;

                return matchesText && matchesMuscle;
            });

            console.log('[FitTrack exercise picker] selected muscle filter:', activeMuscle || 'All Muscles');
            console.log('[FitTrack exercise picker] search term:', query);
            console.log('[FitTrack exercise picker] number of rendered exercises:', filtered.length);

            return filtered;
        };

        const renderExerciseResults = (results) => {
            if (!exerciseResults || !exerciseSearch) {
                return;
            }

            exerciseResults.innerHTML = '';
            const uniqueResults = dedupeExercises(results);

            if (exerciseCount) {
                exerciseCount.textContent = `Showing ${uniqueResults.length} ${uniqueResults.length === 1 ? 'exercise' : 'exercises'}`;
            }

            if (!uniqueResults.length) {
                const empty = document.createElement('div');
                empty.className = 'exercise-empty-result';
                empty.textContent = 'No matching exercises';
                exerciseResults.append(empty);
                return;
            }

            uniqueResults.forEach((exercise) => {
                const normalizedExercise = normalizeExercise(exercise);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'exercise-result';
                const textWrap = document.createElement('span');
                const name = document.createElement('strong');
                const meta = document.createElement('small');
                const selectLabel = document.createElement('span');

                name.textContent = normalizedExercise.name;
                meta.textContent = `${normalizedExercise.muscle_group}${normalizedExercise.equipment ? ` | ${normalizedExercise.equipment}` : ''}`;

                if (normalizedExercise.source === 'custom') {
                    const customBadge = document.createElement('span');
                    customBadge.className = 'exercise-result__badge';
                    customBadge.textContent = 'Custom';
                    meta.append(customBadge);
                }

                selectLabel.className = 'exercise-result__select';
                selectLabel.setAttribute('aria-hidden', 'true');
                selectLabel.textContent = 'Select';
                textWrap.append(name, meta);
                button.append(textWrap, selectLabel);
                button.addEventListener('click', () => selectExercise(normalizedExercise));
                exerciseResults.append(button);
            });
        };

        const loadExerciseResults = async () => {
            if (!exerciseResults || !exerciseSearch) {
                return;
            }

            exerciseResults.innerHTML = '<div class="exercise-empty-result">Loading exercises...</div>';

            if (exerciseCount) {
                exerciseCount.textContent = 'Loading exercises...';
            }

            await loadAllExercises();
            renderExerciseResults(getFilteredExercises());
        };

        const openExerciseModal = () => {
            if (!exerciseModal || !exerciseSearch) {
                return;
            }

            exerciseModal.hidden = false;
            document.body.classList.add('modal-open');
            exerciseSearch.value = exerciseNameInput.value;
            loadExerciseResults();
            window.setTimeout(() => {
                exerciseSearch.focus();
                exerciseSearch.select();
            }, 0);
        };

        const setCustomExerciseMessage = (message, type = 'error') => {
            if (!customExerciseMessage) {
                return;
            }

            customExerciseMessage.textContent = message;
            customExerciseMessage.classList.toggle('success', type === 'success');
            customExerciseMessage.hidden = !message;
        };

        const closeCustomExerciseModal = () => {
            if (!customExerciseModal) {
                return;
            }

            closeCustomMuscleMenu();
            customExerciseModal.hidden = true;
            setCustomExerciseMessage('');

            if (!exerciseModal || exerciseModal.hidden) {
                document.body.classList.remove('modal-open');
            }
        };

        const openCustomExerciseModal = () => {
            if (!customExerciseModal || !customExerciseName || !customExerciseMuscle) {
                return;
            }

            customExerciseModal.hidden = false;
            document.body.classList.add('modal-open');
            customExerciseName.value = exerciseSearch?.value.trim() || exerciseNameInput.value.trim();
            setCustomMuscle('', 'Choose Muscle Group');
            setCustomExerciseMessage('');

            window.setTimeout(() => {
                customExerciseName.focus();
                customExerciseName.select();
            }, 0);
        };

        const createCustomExercise = async (event) => {
            event.preventDefault();

            if (!customExerciseName || !customExerciseMuscle) {
                return;
            }

            const exerciseName = customExerciseName.value.trim().replace(/\s+/g, ' ');
            const selectedMuscle = customExerciseMuscle.value.trim().replace(/\s+/g, ' ');
            const otherMuscle = customMuscleOther?.value.trim().replace(/\s+/g, ' ') || '';
            const muscleGroup = customMuscleOtherField && !customMuscleOtherField.hidden ? otherMuscle : selectedMuscle;

            if (!exerciseName || !muscleGroup) {
                setCustomExerciseMessage('Exercise name and muscle group are required.');
                return;
            }

            const submitButton = customExerciseForm?.querySelector('button[type="submit"]');
            const originalText = submitButton?.textContent || 'Create Exercise';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Creating...';
            }

            try {
                const response = await fetch(customExerciseApiUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        exercise_name: exerciseName,
                        muscle_group: muscleGroup,
                    }),
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.error || 'Could not create this exercise.');
                }

                const createdExercise = normalizeExercise(payload.exercise || {
                    name: exerciseName,
                    muscleGroup,
                    muscle_group: muscleGroup,
                    source: 'custom',
                });

                allExercises = dedupeExercises([...allExercises, createdExercise]);
                refreshMuscleOptions();
                closeCustomExerciseModal();
                activeMuscle = '';
                exerciseMuscleOptions.forEach((item) => {
                    item.classList.toggle('active', item.value === '');
                });

                if (exerciseMuscleLabel) {
                    exerciseMuscleLabel.textContent = 'All Muscles';
                }

                if (exerciseSearch) {
                    exerciseSearch.value = createdExercise.name;
                }

                renderExerciseResults(getFilteredExercises());
            } catch (error) {
                setCustomExerciseMessage(error.message || 'Could not create this exercise.');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }
        };

        exerciseNameInput.addEventListener('input', updateExerciseMatch);
        exerciseNameInput.addEventListener('change', updateExerciseMatch);

        exerciseOpenButtons.forEach((button) => {
            button.addEventListener('click', openExerciseModal);
        });

        exerciseCloseButtons.forEach((button) => {
            button.addEventListener('click', closeExerciseModal);
        });

        if (customExerciseOpenButton) {
            customExerciseOpenButton.addEventListener('click', openCustomExerciseModal);
        }

        customExerciseCloseButtons.forEach((button) => {
            button.addEventListener('click', closeCustomExerciseModal);
        });

        if (customExerciseForm) {
            customExerciseForm.addEventListener('submit', createCustomExercise);
        }

        if (exerciseSearch) {
            exerciseSearch.addEventListener('input', loadExerciseResults);
        }

        if (exerciseMuscleToggle) {
            exerciseMuscleToggle.addEventListener('click', toggleMuscleMenu);
        }

        if (workoutDurationTrigger && workoutDurationPicker) {
            workoutDurationTrigger.addEventListener('click', () => {
                renderDurationOptions();
                workoutDurationPicker.hidden = !workoutDurationPicker.hidden;
            });
        }

        if (customMuscleOpenButton) {
            customMuscleOpenButton.addEventListener('click', openCustomMuscleMenu);
        }

        customMuscleCloseButtons.forEach((button) => {
            button.addEventListener('click', closeCustomMuscleMenu);
        });

        bindMuscleOptions();
        bindCustomMuscleOptions();

        document.addEventListener('click', (event) => {
            if (exerciseMuscleSelect && exerciseMuscleMenu && !exerciseMuscleMenu.hidden && !exerciseMuscleSelect.contains(event.target)) {
                closeMuscleMenu();
            }

            if (workoutDurationField && workoutDurationPicker && !workoutDurationPicker.hidden && !workoutDurationField.contains(event.target)) {
                closeDurationPicker();
            }

        });

        if (exerciseClearButton && exerciseSearch) {
            exerciseClearButton.addEventListener('click', () => {
                exerciseSearch.value = '';
                loadExerciseResults();
                exerciseSearch.focus();
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (customMuscleModal && !customMuscleModal.hidden) {
                closeCustomMuscleMenu();
                return;
            }

            if (customExerciseModal && !customExerciseModal.hidden) {
                closeCustomExerciseModal();
                return;
            }

            if (exerciseModal && !exerciseModal.hidden) {
                closeExerciseModal();
            }

            closeDurationPicker();
        });

        updateExerciseMatch();
        if (workoutDurationLabel) {
            workoutDurationLabel.textContent = formatDuration(workoutRepsInput?.value);
        }
    }

    const mealScanModal = document.querySelector('[data-meal-scan-modal]');
    const mealScanRoot = document.querySelector('[data-meal-scan-root]');

    if (mealScanModal && mealScanRoot) {
        const apiUrl = window.FITTRACK_FOOD_API || ('http://' + window.location.hostname + ':8000/predict');
        const saveUrl = window.FITTRACK_SAVE_SCANNED_MEAL || 'save_scanned_meal.php';
        const openButtons = document.querySelectorAll('[data-meal-scan-open]');
        const closeButtons = document.querySelectorAll('[data-meal-scan-close]');
        const modeButtons = mealScanRoot.querySelectorAll('[data-meal-scan-mode]');
        const modalTitle = mealScanModal.querySelector('[data-meal-modal-title]');
        const modalSubtitle = mealScanModal.querySelector('[data-meal-modal-subtitle]');
        const cameraPanel = mealScanRoot.querySelector('[data-meal-camera-panel]');
        const uploadPanel = mealScanRoot.querySelector('[data-meal-upload-panel]');
        const video = mealScanRoot.querySelector('[data-meal-camera-video]');
        const cameraEmpty = mealScanRoot.querySelector('[data-meal-camera-empty]');
        const startCameraButton = mealScanRoot.querySelector('[data-meal-camera-start]');
        const captureButton = mealScanRoot.querySelector('[data-meal-camera-capture]');
        const fileInput = mealScanRoot.querySelector('[data-meal-file-input]');
        const canvas = mealScanRoot.querySelector('[data-meal-canvas]');
        const previewPanel = mealScanRoot.querySelector('[data-meal-preview-panel]');
        const previewImage = mealScanRoot.querySelector('[data-meal-preview]');
        const analyzingIndicator = mealScanRoot.querySelector('[data-meal-analyzing]');
        const resetButtons = mealScanRoot.querySelectorAll('[data-meal-reset]');
        const resultPanel = mealScanRoot.querySelector('[data-meal-result-panel]');
        const statusBox = mealScanRoot.querySelector('[data-meal-scan-status]');
        const toastBox = mealScanModal.querySelector('[data-meal-scan-toast]');
        const foodName = mealScanRoot.querySelector('[data-meal-food-name]');
        const confidenceLabel = mealScanRoot.querySelector('[data-meal-confidence-label]');
        const resultMessage = mealScanRoot.querySelector('[data-meal-result-message]');
        const sgBadge = mealScanRoot.querySelector('[data-meal-sg-badge]');
        const resultImage = mealScanRoot.querySelector('[data-meal-result-image]');
        const foodDescription = mealScanRoot.querySelector('[data-meal-food-description]');
        const per100Text = mealScanRoot.querySelector('[data-meal-per-100]');
        const nutritionLabel = mealScanRoot.querySelector('[data-meal-nutrition-label]');
        const servingButtons = mealScanRoot.querySelectorAll('[data-serving-choice]');
        const servingSmall = mealScanRoot.querySelector('[data-serving-small]');
        const servingRegular = mealScanRoot.querySelector('[data-serving-regular]');
        const servingLarge = mealScanRoot.querySelector('[data-serving-large]');
        const customServingWrap = mealScanRoot.querySelector('[data-meal-custom-serving]');
        const servingInput = mealScanRoot.querySelector('[data-meal-serving-grams]');
        const saveDateInput = mealScanRoot.querySelector('[data-meal-save-date]');
        const saveTimeInput = mealScanRoot.querySelector('[data-meal-save-time]');
        const saveDateLabel = mealScanRoot.querySelector('[data-meal-date-label]');
        const saveTimeLabel = mealScanRoot.querySelector('[data-meal-time-label]');
        const saveDateTrigger = mealScanRoot.querySelector('[data-meal-date-trigger]');
        const saveTimeTrigger = mealScanRoot.querySelector('[data-meal-time-trigger]');
        const totalCalories = mealScanRoot.querySelector('[data-meal-total-calories]');
        const totalProtein = mealScanRoot.querySelector('[data-meal-total-protein]');
        const totalCarbs = mealScanRoot.querySelector('[data-meal-total-carbs]');
        const totalFats = mealScanRoot.querySelector('[data-meal-total-fats]');
        const sourceText = mealScanRoot.querySelector('[data-meal-source]');
        const saveButton = mealScanRoot.querySelector('[data-meal-save]');
        const manualInsteadButton = mealScanRoot.querySelector('[data-meal-manual-instead]');
        const mealHistoryList = document.querySelector('[data-meal-history-list]');
        const mealHistoryEmpty = document.querySelector('[data-meal-history-empty]');
        const mealCount = document.querySelector('[data-meal-count]');
        const mealHistoryPanel = document.querySelector('[data-meal-history-panel]');
        const mealHistoryOpen = document.querySelector('[data-meal-history-open]');
        const mealHistoryModal = document.querySelector('[data-meal-history-modal]');
        const mealHistoryDate = document.querySelector('[data-meal-history-date]');
        const mealHistoryModalContent = document.querySelector('[data-meal-history-modal-content]');
        const mealHistoryCloseButtons = document.querySelectorAll('[data-meal-history-close]');
        const totalCaloriesSummary = mealHistoryPanel?.querySelector('[data-meal-total-calories]');
        const totalProteinSummary = mealHistoryPanel?.querySelector('[data-meal-total-protein]');
        const totalCarbsSummary = mealHistoryPanel?.querySelector('[data-meal-total-carbs]');
        const totalFatsSummary = mealHistoryPanel?.querySelector('[data-meal-total-fats]');
        let stream = null;
        let selectedImage = null;
        let selectedPreviewUrl = '';
        let predictionPayload = null;
        let activePrediction = null;
        let activeNutrition = null;
        let activeServingGrams = 250;
        let regularServingGrams = 250;

        const showToast = (message) => {
            if (!toastBox) {
                return;
            }

            toastBox.textContent = message;
            toastBox.hidden = false;
            toastBox.classList.remove('is-showing');
            window.requestAnimationFrame(() => {
                toastBox.classList.add('is-showing');
            });
        };

        const hideToast = () => {
            if (!toastBox) {
                return;
            }

            toastBox.hidden = true;
            toastBox.classList.remove('is-showing');
            toastBox.textContent = '';
        };

        const setStatus = (message, type = 'neutral') => {
            if (!statusBox) {
                return;
            }

            statusBox.textContent = message;
            statusBox.dataset.statusType = type;
            statusBox.hidden = message === '';
        };

        const stopCamera = () => {
            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
                stream = null;
            }

            if (video) {
                video.srcObject = null;
            }

            if (captureButton) {
                captureButton.disabled = true;
            }

            if (cameraEmpty) {
                cameraEmpty.hidden = false;
            }
        };

        const openMealScan = () => {
            mealScanModal.hidden = false;
            document.body.classList.add('modal-open');
            setStatus('');
            hideToast();
            if (modalTitle) {
                modalTitle.textContent = 'Scan Your Meal';
            }
            if (modalSubtitle) {
                modalSubtitle.textContent = 'Position the food in frame';
            }
        };

        const openManualEntry = () => {
            if (saveDateInput) {
                const manualDate = document.getElementById('meal_date');
                if (manualDate) {
                    manualDate.value = saveDateInput.value;
                }
            }
            if (saveTimeInput) {
                const manualTime = document.getElementById('meal_time');
                if (manualTime) {
                    manualTime.value = saveTimeInput.value;
                }
            }
            closeMealScan();
            setManualMealForm(true);
            manualMealForm?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        };

        const closeMealScan = () => {
            resetScan();
            mealScanModal.hidden = true;
            document.body.classList.remove('modal-open');
            stopCamera();
            hideToast();
        };

        const setMode = (mode) => {
            modeButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.mealScanMode === mode);
            });

            if (cameraPanel) {
                cameraPanel.hidden = mode !== 'camera';
            }

            if (uploadPanel) {
                uploadPanel.hidden = mode !== 'upload';
            }

            if (mode !== 'camera') {
                stopCamera();
            }

            if (modalTitle && !mealScanRoot.classList.contains('has-result')) {
                modalTitle.textContent = 'Scan Your Meal';
            }

            if (modalSubtitle && !mealScanRoot.classList.contains('has-result')) {
                modalSubtitle.textContent = mode === 'upload' ? 'Upload a photo of your food' : 'Position the food in frame';
            }

            setStatus('');
        };

        const clearPreviewUrl = () => {
            if (selectedPreviewUrl) {
                URL.revokeObjectURL(selectedPreviewUrl);
                selectedPreviewUrl = '';
            }
        };

        const resetScan = () => {
            clearPreviewUrl();
            selectedImage = null;
            predictionPayload = null;
            activePrediction = null;
            activeNutrition = null;
            activeServingGrams = 250;
            regularServingGrams = 250;
            mealScanRoot.classList.remove('has-image', 'has-result');

            if (previewImage) {
                previewImage.removeAttribute('src');
            }

            if (fileInput) {
                fileInput.value = '';
            }

            if (previewPanel) {
                previewPanel.hidden = true;
            }

            if (resultPanel) {
                resultPanel.hidden = true;
            }

            if (analyzingIndicator) {
                analyzingIndicator.hidden = true;
            }

            if (resultImage) {
                resultImage.removeAttribute('src');
            }

            if (manualInsteadButton) {
                manualInsteadButton.hidden = true;
            }

            if (per100Text) {
                per100Text.textContent = '';
            }

            setStatus('');
        };

        const setSelectedImage = (blob) => {
            resetScan();
            selectedImage = blob;
            selectedPreviewUrl = URL.createObjectURL(blob);

            if (previewImage) {
                previewImage.src = selectedPreviewUrl;
            }

            if (previewPanel) {
                previewPanel.hidden = false;
            }

            mealScanRoot.classList.add('has-image');
            analyzeMeal();
        };

        const startCamera = async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                setStatus('Camera is not available in this browser. Upload a photo instead.', 'error');
                setMode('upload');
                return;
            }

            try {
                stopCamera();
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                    audio: false,
                });

                if (video) {
                    video.srcObject = stream;
                }

                if (captureButton) {
                    captureButton.disabled = false;
                }

                if (cameraEmpty) {
                    cameraEmpty.hidden = true;
                }

                setStatus('');
            } catch (error) {
                setStatus('Camera permission was blocked. Upload a photo instead.', 'error');
                setMode('upload');
            }
        };

        const captureMeal = () => {
            if (!video || !canvas || !video.videoWidth || !video.videoHeight) {
                setStatus('Camera is still warming up. Try again in a moment.', 'error');
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            canvas.toBlob((blob) => {
                if (!blob) {
                    setStatus('Could not capture the image. Try again.', 'error');
                    return;
                }

                setSelectedImage(blob);
            }, 'image/jpeg', 0.9);
        };

        const formatNumber = (value, decimals = 1) => {
            const number = Number.parseFloat(value);

            if (!Number.isFinite(number)) {
                return '';
            }

            return (Math.round(number * (10 ** decimals)) / (10 ** decimals)).toFixed(decimals);
        };

        const nutritionForServing = (nutrition, grams) => {
            const multiplier = Number.parseFloat(grams || '100') / 100;

            if (!nutrition || !Number.isFinite(multiplier) || multiplier <= 0) {
                return {
                    calories: '',
                    protein: '',
                    carbs: '',
                    fats: '',
                };
            }

            return {
                calories: Math.round((Number.parseFloat(nutrition.calories) || 0) * multiplier),
                protein: formatNumber((Number.parseFloat(nutrition.protein) || 0) * multiplier, 2),
                carbs: formatNumber((Number.parseFloat(nutrition.carbs) || 0) * multiplier, 2),
                fats: formatNumber((Number.parseFloat(nutrition.fats) || 0) * multiplier, 2),
            };
        };

        const updateNutritionInputs = () => {
            const totals = nutritionForServing(activeNutrition, activeServingGrams || servingInput?.value || '100');

            if (totalCalories) {
                totalCalories.textContent = totals.calories;
            }

            if (totalProtein) {
                totalProtein.textContent = formatNumber(totals.protein, 0);
            }

            if (totalCarbs) {
                totalCarbs.textContent = formatNumber(totals.carbs, 0);
            }

            if (totalFats) {
                totalFats.textContent = formatNumber(totals.fats, 0);
            }

            if (nutritionLabel) {
                nutritionLabel.textContent = `NUTRITION FOR ${Math.round(activeServingGrams || 100)}G`;
            }
        };

        const readableFood = (value) => {
            return (value || '')
                .toString()
                .replace(/[_-]+/g, ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        };

        const confidenceTone = (confidence) => {
            if (confidence >= 80) {
                return 'high';
            }

            if (confidence >= 50) {
                return 'medium';
            }

            return 'low';
        };

        const formatMacro = (value) => {
            if (value === null || value === undefined || value === '') {
                return '--';
            }

            const number = Number.parseFloat(value);
            return Number.isFinite(number) ? `${number.toFixed(1)} g` : '--';
        };

        const formatMealDate = (value) => {
            if (!value) {
                return '';
            }

            const [year, month, day] = value.split('-').map((part) => Number.parseInt(part, 10));

            if (!year || !month || !day) {
                return value;
            }

            return new Date(year, month - 1, day).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
        };

        const formatMealTime = (value) => {
            if (!value) {
                return '--';
            }

            const [hours, minutes] = value.split(':').map((part) => Number.parseInt(part, 10));

            if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
                return '--';
            }

            const date = new Date();
            date.setHours(hours, minutes, 0, 0);

            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
            }).toLowerCase();
        };

        const updateDateTimeLabels = () => {
            if (saveDateInput && saveDateLabel) {
                const today = new Date();
                const todayValue = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                saveDateLabel.textContent = saveDateInput.value === todayValue ? 'Today' : formatMealDate(saveDateInput.value);
            }

            if (saveTimeInput && saveTimeLabel) {
                saveTimeLabel.textContent = formatMealTime(saveTimeInput.value);
            }
        };

        const mealKey = (value) => {
            return (value || '').toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        };

        const singaporeFoods = new Set([
            'bak_kut_teh',
            'char_kway_teow',
            'chicken_rice',
            'chilli_crab',
            'curry_puff',
            'fried_carrot_cake',
            'hokkien_mee',
            'ice_kacang',
            'kaya_toast',
            'laksa',
            'lor_mee',
            'mee_siam',
            'nasi_lemak',
            'popiah',
            'porridge',
            'roti_prata',
            'satay',
            'sliced_fish_soup',
            'tau_suan',
            'yong_tau_foo',
        ]);

        const isSingaporeMeal = (meal) => {
            return singaporeFoods.has(mealKey(meal?.food_name)) || singaporeFoods.has(mealKey(meal?.meal_name));
        };

        const todayValue = () => {
            const today = new Date();
            return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        };

        const macroValue = (value) => {
            if (value === null || value === undefined || value === '') {
                return '0';
            }

            const number = Number.parseFloat(value);
            return Number.isFinite(number) ? number.toFixed(1) : '0';
        };

        const createMetricCard = (label, value, unit = '', empty = false, compact = false) => {
            const card = document.createElement('div');
            card.className = `meal-summary-card ${empty ? 'is-empty' : ''} ${compact ? 'is-compact' : ''}`;
            const labelEl = document.createElement('span');
            labelEl.className = 'stat-label';
            labelEl.textContent = label;
            const valueEl = document.createElement('p');
            valueEl.className = 'stat-value';
            valueEl.textContent = String(value);

            if (unit) {
                const unitEl = document.createElement('span');
                unitEl.className = 'unit';
                unitEl.textContent = ` ${unit}`;
                valueEl.append(unitEl);
            }

            card.append(labelEl, valueEl);
            return card;
        };

        const createMacroGrid = (totals, empty = false, compact = false) => {
            const grid = document.createElement('div');
            grid.className = `meal-summary-grid ${compact ? 'is-compact' : ''}`;
            grid.append(
                createMetricCard('CAL', totals?.calories || 0, '', empty, compact),
                createMetricCard('PROT', macroValue(totals?.protein_g), 'g', empty, compact),
                createMetricCard('CARB', macroValue(totals?.carbs_g), 'g', empty, compact),
                createMetricCard('FAT', macroValue(totals?.fats_g), 'g', empty, compact),
            );
            return grid;
        };

        const createMealCard = (meal, index, showDate = true) => {
            const source = meal.source === 'scanned' ? 'scanned' : 'manual';
            const serving = meal.serving_grams ? `${Math.round(Number.parseFloat(meal.serving_grams))} g` : '100 g';
            const card = document.createElement('article');
            card.className = `meal-history-card ${showDate ? '' : 'meal-history-card--time-only'}`;
            card.dataset.mealCard = '';

            const top = document.createElement('div');
            top.className = 'meal-history-card__top';

            const title = document.createElement('div');
            title.className = 'meal-history-title';
            const number = document.createElement('span');
            number.className = 'meal-number-badge';
            number.textContent = String(index + 1);

            const titleText = document.createElement('div');
            const nameRow = document.createElement('div');
            nameRow.className = 'meal-history-name-row';
            const name = document.createElement('strong');
            name.textContent = `${meal.meal_name || ''}${isSingaporeMeal(meal) ? ' SG' : ''}`;
            const pill = document.createElement('span');
            pill.className = `meal-source-pill ${source === 'scanned' ? 'ai' : 'manual'}`;
            const pillIcon = document.createElement('i');
            pillIcon.className = `ti ${source === 'scanned' ? 'ti-sparkles' : 'ti-pencil'}`;
            pillIcon.setAttribute('aria-hidden', 'true');
            pill.append(pillIcon, source === 'scanned' ? 'AI SCAN' : 'MANUAL');
            const servingChip = document.createElement('span');
            servingChip.className = 'meal-serving-chip';
            servingChip.textContent = `Serving ${serving}`;
            nameRow.append(name, pill);
            titleText.append(nameRow, servingChip);
            title.append(number, titleText);

            const meta = document.createElement('div');
            meta.className = 'meal-history-meta';
            if (showDate) {
                const date = document.createElement('time');
                date.dateTime = meal.meal_date || '';
                date.textContent = formatMealDate(meal.meal_date);
                meta.append(date);
            }
            const time = document.createElement('small');
            time.textContent = formatMealTime(meal.meal_time);
            meta.append(time);
            top.append(title, meta);

            const stats = document.createElement('div');
            stats.className = 'meal-history-card__stats';
            [
                ['CAL', meal.calories ?? '', ''],
                ['PROTEIN', formatMacro(meal.protein_g).replace(' g', ''), 'g'],
                ['CARBS', formatMacro(meal.carbs_g).replace(' g', ''), 'g'],
                ['FATS', formatMacro(meal.fats_g).replace(' g', ''), 'g'],
            ].forEach(([label, value, unit]) => {
                const item = document.createElement('div');
                const small = document.createElement('small');
                const bold = document.createElement('b');
                small.textContent = label;
                bold.textContent = String(value);
                if (unit && value !== '--') {
                    const unitElement = document.createElement('span');
                    unitElement.textContent = unit;
                    bold.append(unitElement);
                }
                item.append(small, bold);
                stats.append(item);
            });

            card.append(top, stats);
            return card;
        };

        const createMealEmptyState = (title, subtitle) => {
            const empty = document.createElement('div');
            empty.className = 'meal-today-empty';
            empty.innerHTML = '<span><i class="ti ti-bowl-spoon" aria-hidden="true"></i></span>';
            const heading = document.createElement('h3');
            heading.textContent = title;
            const text = document.createElement('p');
            text.textContent = subtitle;
            empty.append(heading, text);
            return empty;
        };

        const typicalServings = {
            chicken_rice: 382,
            laksa: 488,
            nasi_lemak: 350,
            pizza: 250,
        };

        const getRegularServing = (food) => {
            return typicalServings[mealKey(food)] || 250;
        };

        const setServingChoice = (choice, grams) => {
            const nextGrams = Math.max(1, Number.parseFloat(grams) || regularServingGrams || 250);
            activeServingGrams = nextGrams;

            if (servingInput) {
                servingInput.value = String(Math.round(nextGrams));
            }

            servingButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.servingChoice === choice);
            });

            if (customServingWrap) {
                customServingWrap.hidden = choice !== 'custom';
            }

            updateNutritionInputs();
        };

        const configureServingOptions = () => {
            regularServingGrams = getRegularServing(activePrediction?.food || activePrediction?.food_display);
            const small = Math.round(regularServingGrams * 0.5);
            const large = Math.round(regularServingGrams * 1.3);

            if (servingSmall) {
                servingSmall.textContent = `${small}g`;
            }

            if (servingRegular) {
                servingRegular.textContent = `${regularServingGrams}g`;
            }

            if (servingLarge) {
                servingLarge.textContent = `${large}g`;
            }

            setServingChoice('regular', regularServingGrams);
        };

        const parseMacroNumber = (value) => {
            const number = Number.parseFloat(value);
            return Number.isFinite(number) ? number : 0;
        };

        const updateMealSummary = (meal) => {
            if (mealCount) {
                const current = Number.parseInt(mealCount.textContent, 10) || 0;
                mealCount.textContent = `${current + 1} LOGGED`;
            }

            mealHistoryPanel?.querySelectorAll('.meal-summary-card.is-empty').forEach((card) => {
                card.classList.remove('is-empty');
            });

            if (totalCaloriesSummary) {
                totalCaloriesSummary.textContent = String((Number.parseInt(totalCaloriesSummary.textContent, 10) || 0) + (Number.parseInt(meal.calories, 10) || 0));
            }

            [
                [totalProteinSummary, meal.protein_g],
                [totalCarbsSummary, meal.carbs_g],
                [totalFatsSummary, meal.fats_g],
            ].forEach(([element, value]) => {
                if (!element) {
                    return;
                }

                const next = parseMacroNumber(element.textContent) + parseMacroNumber(value);
                element.textContent = next.toFixed(1);
            });
        };

        const prependMealHistoryRow = (meal) => {
            if (!mealHistoryList || !meal) {
                return;
            }

            const currentCount = mealHistoryList.querySelectorAll('[data-meal-card]').length;
            if (meal.meal_date !== todayValue()) {
                return;
            }

            const newCard = createMealCard(meal, currentCount, true);
            newCard.classList.add('meal-history-row-new');
            mealHistoryList.prepend(newCard);
            Array.from(mealHistoryList.querySelectorAll('.meal-number-badge')).forEach((badge, index) => {
                badge.textContent = String(index + 1);
            });
            mealHistoryList.hidden = false;

            if (mealHistoryEmpty) {
                mealHistoryEmpty.hidden = true;
            }

            updateMealSummary(meal);
            window.setTimeout(() => {
                newCard.classList.remove('meal-history-row-new');
            }, 1600);
            return;

            const card = document.createElement('article');
            const source = meal.source === 'scanned' ? 'scanned' : 'manual';
            const serving = meal.serving_grams ? `${Math.round(Number.parseFloat(meal.serving_grams))} g` : '100 g';
            card.className = 'meal-history-card meal-history-row-new';
            card.dataset.mealCard = '';

            const top = document.createElement('div');
            top.className = 'meal-history-card__top';

            const title = document.createElement('div');
            title.className = 'meal-history-title';
            const number = document.createElement('span');
            number.className = 'meal-number-badge';
            number.textContent = String(currentCount + 1);
            const titleText = document.createElement('div');
            const nameRow = document.createElement('div');
            nameRow.className = 'meal-history-name-row';
            const name = document.createElement('strong');
            name.textContent = `${meal.meal_name || ''}${isSingaporeMeal(meal) ? ' 🇸🇬' : ''}`;
            name.textContent = `${meal.meal_name || ''}${isSingaporeMeal(meal) ? ' SG' : ''}`;
            const pill = document.createElement('span');
            pill.className = `meal-source-pill ${source === 'scanned' ? 'ai' : 'manual'}`;
            const pillIcon = document.createElement('i');
            pillIcon.className = `ti ${source === 'scanned' ? 'ti-sparkles' : 'ti-pencil'}`;
            pillIcon.setAttribute('aria-hidden', 'true');
            pill.append(pillIcon, source === 'scanned' ? 'AI SCAN' : 'MANUAL');
            const servingChip = document.createElement('span');
            servingChip.className = 'meal-serving-chip';
            servingChip.textContent = `Serving ${serving}`;
            nameRow.append(name, pill);
            titleText.append(nameRow, servingChip);
            title.append(number, titleText);

            const meta = document.createElement('div');
            meta.className = 'meal-history-meta';
            const date = document.createElement('time');
            date.dateTime = meal.meal_date || '';
            date.textContent = formatMealDate(meal.meal_date);
            const time = document.createElement('small');
            time.textContent = formatMealTime(meal.meal_time);
            meta.append(date, time);
            top.append(title, meta);

            const stats = document.createElement('div');
            stats.className = 'meal-history-card__stats';
            [
                ['CAL', meal.calories ?? '', ''],
                ['PROTEIN', formatMacro(meal.protein_g).replace(' g', ''), 'g'],
                ['CARBS', formatMacro(meal.carbs_g).replace(' g', ''), 'g'],
                ['FATS', formatMacro(meal.fats_g).replace(' g', ''), 'g'],
            ].forEach(([label, value, unit]) => {
                const item = document.createElement('div');
                const small = document.createElement('small');
                const bold = document.createElement('b');
                small.textContent = label;
                bold.textContent = String(value);
                if (unit && value !== '--') {
                    const unitElement = document.createElement('span');
                    unitElement.textContent = unit;
                    bold.append(unitElement);
                }
                item.append(small, bold);
                stats.append(item);
            });

            card.append(top, stats);
            mealHistoryList.prepend(card);
            Array.from(mealHistoryList.querySelectorAll('.meal-number-badge')).forEach((badge, index) => {
                badge.textContent = String(index + 1);
            });
            mealHistoryList.hidden = false;

            if (mealHistoryEmpty) {
                mealHistoryEmpty.hidden = true;
            }

            updateMealSummary(meal);
            window.setTimeout(() => {
                card.classList.remove('meal-history-row-new');
            }, 1600);
        };

        const renderMealHistoryModal = (payload) => {
            if (!mealHistoryModalContent) {
                return;
            }

            mealHistoryModalContent.innerHTML = '';
            const meals = Array.isArray(payload.meals) ? payload.meals : [];
            const empty = meals.length === 0;

            if (!empty) {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'meal-history-day-heading';
                const dayTitle = document.createElement('div');
                const heading = document.createElement('h3');
                heading.textContent = payload.display_date || formatMealDate(payload.date);
                dayTitle.append(heading);
                const count = document.createElement('span');
                count.textContent = `${meals.length} ${meals.length === 1 ? 'MEAL' : 'MEALS'}`;
                dayHeader.append(dayTitle, count);
                mealHistoryModalContent.append(dayHeader, createMacroGrid(payload.totals, false, true));

                const list = document.createElement('div');
                list.className = 'meal-history-list';
                meals.forEach((meal, index) => list.append(createMealCard(meal, index, false)));
                mealHistoryModalContent.append(list);
                return;
            }

            mealHistoryModalContent.append(
                createMacroGrid({ calories: 0, protein_g: 0, carbs_g: 0, fats_g: 0 }, true, false),
                createMealEmptyState('No meals logged on this date', 'Try picking a different date from the calendar above'),
            );
        };

        const loadMealHistoryDate = async () => {
            if (!mealHistoryDate || !mealHistoryModalContent) {
                return;
            }

            mealHistoryModalContent.innerHTML = '<div class="meal-history-loading">Loading meals...</div>';

            try {
                const response = await fetch(`meals_by_date.php?date=${encodeURIComponent(mealHistoryDate.value)}`, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.error || 'Unable to load meal history.');
                }

                renderMealHistoryModal(payload);
            } catch (error) {
                mealHistoryModalContent.innerHTML = '';
                mealHistoryModalContent.append(createMealEmptyState('Meal history unavailable', error.message || 'Try again in a moment'));
            }
        };

        const openMealHistoryModal = () => {
            if (!mealHistoryModal) {
                return;
            }

            mealHistoryModal.hidden = false;
            document.body.classList.add('modal-open');
            loadMealHistoryDate();
        };

        const closeMealHistoryModal = () => {
            if (!mealHistoryModal) {
                return;
            }

            mealHistoryModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        const renderPrediction = () => {
            if (!activePrediction) {
                return;
            }

            const confidence = Number.parseFloat(activePrediction.confidence) || 0;

            if (foodName) {
                foodName.textContent = activePrediction.food_display || readableFood(activePrediction.food);
            }

            if (confidenceLabel) {
                confidenceLabel.textContent = `${formatNumber(confidence, 2)}% MATCH`;
                confidenceLabel.dataset.confidence = confidenceTone(confidence);
            }

            if (sgBadge) {
                sgBadge.hidden = activePrediction.is_singapore_food !== true;
            }

            if (foodDescription) {
                foodDescription.textContent = activeNutrition?.description || 'Review the serving size before saving.';
            }

            if (sourceText) {
                sourceText.textContent = activeNutrition?.source ? `Source: ${activeNutrition.source}` : 'No nutrition source available.';
            }

            if (per100Text) {
                per100Text.textContent = activeNutrition?.calories ? `Per 100g: ${activeNutrition.calories} kcal` : 'Per 100g';
            }

            if (resultImage && previewImage?.src) {
                resultImage.src = previewImage.src;
            }

            if (manualInsteadButton) {
                manualInsteadButton.hidden = false;
            }

            configureServingOptions();
            updateNutritionInputs();
        };

        const analyzeMeal = async () => {
            if (!selectedImage) {
                setStatus('Choose or capture a meal photo first.', 'error');
                return;
            }

            if (analyzingIndicator) {
                analyzingIndicator.hidden = false;
            }

            setStatus('Analyzing meal photo...', 'neutral');

            try {
                const formData = new FormData();
                formData.append('file', selectedImage, 'meal.jpg');
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 400) {
                        throw new Error("We couldn't read that image. Try a different photo.");
                    }

                    throw new Error('AI service unavailable. Please try again or enter manually.');
                }

                predictionPayload = payload;
                activePrediction = payload.prediction || null;
                activeNutrition = payload.nutrition || null;

                if (!activePrediction) {
                    throw new Error('The AI response did not include a prediction.');
                }

                renderPrediction();
                if (resultPanel) {
                    resultPanel.hidden = false;
                }

                mealScanRoot.classList.add('has-result');
                if (modalTitle) {
                    modalTitle.textContent = 'Scan Result';
                }
                if (modalSubtitle) {
                    modalSubtitle.textContent = 'Review and adjust before saving';
                }
                setStatus('');
            } catch (error) {
                const message = error instanceof TypeError
                    ? 'AI food scan is currently unavailable. Please try again later.'
                    : (error.message || 'AI food scan is currently unavailable. Please try again later.');
                resetScan();
                setMode('upload');
                setStatus(message, 'error');
            } finally {
                if (analyzingIndicator) {
                    analyzingIndicator.hidden = true;
                }
            }
        };

        const saveScannedMeal = async () => {
            if (!activePrediction) {
                setStatus('Analyze a meal before saving.', 'error');
                return;
            }

            const payload = {
                meal_date: saveDateInput?.value || '',
                meal_time: saveTimeInput?.value || '',
                food_name: activePrediction.food || '',
                food_display: activePrediction.food_display || readableFood(activePrediction.food),
                serving_grams: String(Math.round(activeServingGrams || Number.parseFloat(servingInput?.value) || 100)),
                calories: totalCalories?.textContent || '',
                protein_g: totalProtein?.textContent || '',
                carbs_g: totalCarbs?.textContent || '',
                fats_g: totalFats?.textContent || '',
                ai_confidence: activePrediction.confidence ?? null,
            };

            const originalText = saveButton?.textContent || "Add to today's meals";

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
            }

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.success !== true) {
                    throw new Error(data.error || 'Unable to save the scanned meal right now.');
                }

                prependMealHistoryRow({
                    ...data.meal,
                    source: 'scanned',
                    food_name: activePrediction.food || '',
                    serving_grams: servingInput?.value || '',
                });
                showToast('Meal saved');
                resetScan();
                window.setTimeout(() => {
                    closeMealScan();
                }, 650);
            } catch (error) {
                setStatus(error.message || 'Unable to save the scanned meal right now.', 'error');
            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = originalText;
                }
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                resetScan();
                openMealScan();
                if (button.hasAttribute('data-meal-scan-prefer-upload')) {
                    setMode('upload');
                } else {
                    setMode('camera');
                    startCamera();
                }
            });
        });
        closeButtons.forEach((button) => button.addEventListener('click', closeMealScan));
        modeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                resetScan();
                setMode(button.dataset.mealScanMode);
            });
        });

        if (startCameraButton) {
            startCameraButton.addEventListener('click', startCamera);
        }

        if (captureButton) {
            captureButton.addEventListener('click', captureMeal);
        }

        if (fileInput) {
            fileInput.addEventListener('change', () => {
                const file = fileInput.files?.[0];

                if (!file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    setStatus('Choose an image file.', 'error');
                    return;
                }

                setSelectedImage(file);
            });
        }

        resetButtons.forEach((button) => button.addEventListener('click', resetScan));

        if (servingInput) {
            servingInput.addEventListener('input', () => {
                setServingChoice('custom', servingInput.value);
            });
        }

        servingButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const choice = button.dataset.servingChoice;

                if (choice === 'small') {
                    setServingChoice('small', Math.round(regularServingGrams * 0.5));
                } else if (choice === 'large') {
                    setServingChoice('large', Math.round(regularServingGrams * 1.3));
                } else if (choice === 'custom') {
                    setServingChoice('custom', servingInput?.value || activeServingGrams);
                    window.setTimeout(() => servingInput?.focus(), 0);
                } else {
                    setServingChoice('regular', regularServingGrams);
                }
            });
        });

        if (saveButton) {
            saveButton.addEventListener('click', saveScannedMeal);
        }

        if (manualInsteadButton) {
            manualInsteadButton.addEventListener('click', openManualEntry);
        }

        if (mealHistoryOpen) {
            mealHistoryOpen.addEventListener('click', openMealHistoryModal);
        }

        mealHistoryCloseButtons.forEach((button) => button.addEventListener('click', closeMealHistoryModal));

        if (mealHistoryModal) {
            mealHistoryModal.addEventListener('click', (event) => {
                if (event.target === mealHistoryModal || event.target.hasAttribute('data-meal-history-close')) {
                    closeMealHistoryModal();
                }
            });
        }

        if (mealHistoryDate) {
            mealHistoryDate.addEventListener('change', loadMealHistoryDate);
            mealHistoryDate.parentElement?.addEventListener('click', () => {
                mealHistoryDate.focus();
                try {
                    if (typeof mealHistoryDate.showPicker === 'function') {
                        mealHistoryDate.showPicker();
                    }
                } catch (error) {
                    mealHistoryDate.click();
                }
            });
        }

        if (saveDateInput) {
            saveDateInput.addEventListener('change', updateDateTimeLabels);
        }

        if (saveTimeInput) {
            saveTimeInput.addEventListener('change', updateDateTimeLabels);
        }

        if (saveDateTrigger && saveDateInput) {
            saveDateTrigger.addEventListener('click', () => {
                saveDateInput.focus();
                if (typeof saveDateInput.showPicker === 'function') {
                    saveDateInput.showPicker();
                } else {
                    saveDateInput.click();
                }
            });
        }

        if (saveTimeTrigger && saveTimeInput) {
            saveTimeTrigger.addEventListener('click', () => {
                saveTimeInput.focus();
                if (typeof saveTimeInput.showPicker === 'function') {
                    saveTimeInput.showPicker();
                } else {
                    saveTimeInput.click();
                }
            });
        }

        updateDateTimeLabels();

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !mealScanModal.hidden) {
                closeMealScan();
            }

            if (event.key === 'Escape' && mealHistoryModal && !mealHistoryModal.hidden) {
                closeMealHistoryModal();
            }
        });
    }

    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const chartType = canvas.dataset.chart;
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        const secondaryValues = JSON.parse(canvas.dataset.secondaryValues || '[]');
        const label = canvas.dataset.label || '';
        const secondaryLabel = canvas.dataset.secondaryLabel || '';
        const monoTheme = canvas.dataset.chartTheme === 'mono';

        if (!labels.length) {
            return;
        }

        const datasets = [{
            label,
            data: values,
            borderColor: '#f5f5f5',
            backgroundColor: chartType === 'bar' ? '#f5f5f5' : (monoTheme ? 'transparent' : 'rgba(245, 245, 245, 0.12)'),
            pointBackgroundColor: '#f5f5f5',
            pointBorderColor: '#f5f5f5',
            pointRadius: chartType === 'line' ? (monoTheme ? 3.5 : 3) : 0,
            pointHoverRadius: chartType === 'line' ? 5 : 0,
            borderWidth: monoTheme ? 2 : undefined,
            tension: 0.35,
            fill: monoTheme ? false : chartType !== 'bar',
            borderRadius: chartType === 'bar' ? 6 : 0,
            maxBarThickness: 42,
        }];

        if (secondaryValues.length) {
            datasets.push({
                label: secondaryLabel,
                data: secondaryValues,
                borderColor: monoTheme ? 'rgba(255, 255, 255, 0.4)' : '#707070',
                backgroundColor: monoTheme ? 'transparent' : 'rgba(112, 112, 112, 0.16)',
                pointBackgroundColor: monoTheme ? 'rgba(255, 255, 255, 0.4)' : '#707070',
                pointBorderColor: monoTheme ? 'rgba(255, 255, 255, 0.4)' : '#707070',
                pointRadius: monoTheme ? 2.5 : 3,
                pointHoverRadius: monoTheme ? 4 : 5,
                borderWidth: monoTheme ? 1.5 : undefined,
                tension: 0.3,
                fill: false,
            });
        }

        new Chart(canvas, {
            type: chartType,
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: !monoTheme,
                        labels: {
                            color: '#a0a0a0',
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                        },
                    },
                    tooltip: monoTheme ? {
                        backgroundColor: '#0a0a0a',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 0.5,
                        titleColor: '#ffffff',
                        bodyColor: 'rgba(255, 255, 255, 0.75)',
                        displayColors: false,
                    } : {},
                },
                scales: {
                    x: {
                        ticks: { color: monoTheme ? 'rgba(255, 255, 255, 0.4)' : '#505050', font: monoTheme ? { size: 10 } : undefined },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        border: { color: 'rgba(255, 255, 255, 0.08)' },
                    },
                    y: {
                        beginAtZero: chartType === 'bar',
                        ticks: { color: monoTheme ? 'rgba(255, 255, 255, 0.4)' : '#505050', font: monoTheme ? { size: 10 } : undefined },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        border: { color: 'rgba(255, 255, 255, 0.08)' },
                    },
                },
            },
        });
    });
});
