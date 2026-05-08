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

    const profileForm = document.querySelector('[data-profile-form]');

    if (profileForm) {
        const measurementSelect = profileForm.querySelector('#measurement_units');
        const profileValueUnits = profileForm.querySelector('[data-profile-value-units]');
        const heightInput = profileForm.querySelector('[data-height-input]');
        const weightInput = profileForm.querySelector('[data-weight-input]');
        const heightLabel = profileForm.querySelector('[data-height-label]');
        const weightLabel = profileForm.querySelector('[data-weight-label]');

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
        });

        updateExerciseMatch();
    }

    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const chartType = canvas.dataset.chart;
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        const secondaryValues = JSON.parse(canvas.dataset.secondaryValues || '[]');
        const label = canvas.dataset.label || '';
        const secondaryLabel = canvas.dataset.secondaryLabel || '';

        if (!labels.length) {
            return;
        }

        const datasets = [{
            label,
            data: values,
            borderColor: '#f5f5f5',
            backgroundColor: chartType === 'bar' ? '#f5f5f5' : 'rgba(245, 245, 245, 0.12)',
            pointBackgroundColor: '#f5f5f5',
            pointBorderColor: '#f5f5f5',
            pointRadius: chartType === 'line' ? 3 : 0,
            pointHoverRadius: chartType === 'line' ? 5 : 0,
            tension: 0.35,
            fill: chartType !== 'bar',
            borderRadius: chartType === 'bar' ? 6 : 0,
            maxBarThickness: 42,
        }];

        if (secondaryValues.length) {
            datasets.push({
                label: secondaryLabel,
                data: secondaryValues,
                borderColor: '#707070',
                backgroundColor: 'rgba(112, 112, 112, 0.16)',
                pointBackgroundColor: '#707070',
                pointBorderColor: '#707070',
                pointRadius: 3,
                pointHoverRadius: 5,
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
                        labels: {
                            color: '#a0a0a0',
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#505050' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        border: { color: 'rgba(255, 255, 255, 0.08)' },
                    },
                    y: {
                        beginAtZero: chartType === 'bar',
                        ticks: { color: '#505050' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        border: { color: 'rgba(255, 255, 255, 0.08)' },
                    },
                },
            },
        });
    });
});
