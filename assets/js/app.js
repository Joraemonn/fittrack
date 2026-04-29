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

    const exerciseNameInput = document.querySelector('[data-exercise-name]');
    const muscleGroupInput = document.querySelector('[data-muscle-group]');
    const exerciseDataElement = document.getElementById('exercise-catalog-data');
    const exercisePreview = document.querySelector('[data-exercise-preview]');
    const exerciseImage = document.querySelector('[data-exercise-image]');
    const exercisePreviewName = document.querySelector('[data-exercise-preview-name]');
    const exercisePreviewMuscle = document.querySelector('[data-exercise-preview-muscle]');
    const exerciseModal = document.querySelector('[data-exercise-modal]');
    const exerciseSearch = document.querySelector('[data-exercise-search]');
    const exerciseResults = document.querySelector('[data-exercise-results]');
    const exerciseMuscleFilter = document.querySelector('[data-exercise-muscle-filter]');
    const exerciseOpenButtons = document.querySelectorAll('[data-exercise-open]');
    const exerciseCloseButtons = document.querySelectorAll('[data-exercise-close]');
    const exerciseCreateButton = document.querySelector('[data-exercise-create]');
    const exerciseClearButton = document.querySelector('[data-exercise-clear]');

    if (exerciseNameInput && muscleGroupInput && exerciseDataElement) {
        const exercises = JSON.parse(exerciseDataElement.textContent || '[]');

        const showExerciseMatch = (match) => {
            if (!match) {
                if (exercisePreview) {
                    exercisePreview.hidden = true;
                }
                return;
            }

            if (exercisePreview && exerciseImage && exercisePreviewName && exercisePreviewMuscle) {
                exerciseImage.src = match.image_url;
                exerciseImage.alt = `${match.name} reference image`;
                exercisePreviewName.textContent = match.name;
                exercisePreviewMuscle.textContent = `Targets ${match.muscle_group}`;
                exercisePreview.hidden = false;
            }
        };

        const findExactExercise = (value) => {
            const search = value.trim().toLowerCase();
            return exercises.find((exercise) => exercise.name.toLowerCase() === search);
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

            exerciseModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        const selectExercise = (exercise) => {
            exerciseNameInput.value = exercise.name;
            muscleGroupInput.value = exercise.muscle_group;
            showExerciseMatch(exercise);
            closeExerciseModal();
        };

        const renderExerciseResults = () => {
            if (!exerciseResults || !exerciseSearch) {
                return;
            }

            const query = exerciseSearch.value.trim().toLowerCase();
            const muscle = exerciseMuscleFilter ? exerciseMuscleFilter.value : '';
            const filteredExercises = exercises.filter((exercise) => {
                const matchesText = !query || exercise.name.toLowerCase().includes(query);
                const matchesMuscle = !muscle || exercise.muscle_group === muscle;
                return matchesText && matchesMuscle;
            });

            exerciseResults.innerHTML = '';

            if (!filteredExercises.length) {
                const empty = document.createElement('div');
                empty.className = 'exercise-empty-result';
                empty.textContent = 'No matching exercises';
                exerciseResults.append(empty);
                return;
            }

            filteredExercises.forEach((exercise) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'exercise-result';
                button.innerHTML = `
                    <img src="${exercise.image_url}" alt="">
                    <span>
                        <strong>${exercise.name}</strong>
                        <small>${exercise.muscle_group}</small>
                    </span>
                    <span class="exercise-result__icon" aria-hidden="true"></span>
                `;
                button.addEventListener('click', () => selectExercise(exercise));
                exerciseResults.append(button);
            });
        };

        const openExerciseModal = () => {
            if (!exerciseModal || !exerciseSearch) {
                return;
            }

            exerciseModal.hidden = false;
            document.body.classList.add('modal-open');
            exerciseSearch.value = exerciseNameInput.value;
            renderExerciseResults();
            window.setTimeout(() => {
                exerciseSearch.focus();
                exerciseSearch.select();
            }, 0);
        };

        const createExerciseFromSearch = () => {
            if (!exerciseSearch) {
                return;
            }

            const exerciseName = exerciseSearch.value.trim();
            const match = findExactExercise(exerciseName);

            if (match) {
                selectExercise(match);
                return;
            }

            exerciseNameInput.value = exerciseName;
            muscleGroupInput.value = '';
            showExerciseMatch(null);
            closeExerciseModal();
            muscleGroupInput.focus();
        };

        exerciseNameInput.addEventListener('input', updateExerciseMatch);
        exerciseNameInput.addEventListener('change', updateExerciseMatch);

        exerciseOpenButtons.forEach((button) => {
            button.addEventListener('click', openExerciseModal);
        });

        exerciseCloseButtons.forEach((button) => {
            button.addEventListener('click', closeExerciseModal);
        });

        if (exerciseSearch) {
            exerciseSearch.addEventListener('input', renderExerciseResults);
        }

        if (exerciseMuscleFilter) {
            exerciseMuscleFilter.addEventListener('change', renderExerciseResults);
        }

        if (exerciseCreateButton) {
            exerciseCreateButton.addEventListener('click', createExerciseFromSearch);
        }

        if (exerciseClearButton && exerciseSearch) {
            exerciseClearButton.addEventListener('click', () => {
                exerciseSearch.value = '';
                renderExerciseResults();
                exerciseSearch.focus();
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && exerciseModal && !exerciseModal.hidden) {
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
            borderColor: '#63f08d',
            backgroundColor: 'rgba(99, 240, 141, 0.16)',
            tension: 0.35,
            fill: chartType !== 'bar',
        }];

        if (secondaryValues.length) {
            datasets.push({
                label: secondaryLabel,
                data: secondaryValues,
                borderColor: '#2be6c8',
                backgroundColor: 'rgba(43, 230, 200, 0.18)',
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
                        labels: { color: '#f4fff7' },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#9dc7aa' },
                        grid: { color: 'rgba(157, 199, 170, 0.08)' },
                    },
                    y: {
                        beginAtZero: chartType === 'bar',
                        ticks: { color: '#9dc7aa' },
                        grid: { color: 'rgba(157, 199, 170, 0.08)' },
                    },
                },
            },
        });
    });
});
