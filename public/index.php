<?php

include(__DIR__ . "/../config.php");

require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/year.php");
require_once(__DIR__ . "/../includes/group.php");
require_once(__DIR__ . "/../includes/term.php");
require_once(__DIR__ . "/../includes/competency.php");
require_once(__DIR__ . "/../includes/skill.php");

$user = Auth::requireUser();

$currentYear = Year::current();
$groups = $currentYear ? Group::all($user['id'], $currentYear['id']) : [];
$terms = $currentYear ? Term::allForYear($currentYear['id']) : [];
$competencies = Competency::all();
$skills = Skill::all($user['id']);

$skillsForJs = array_map(function ($skill) {
    $ids = [];
    if (!empty($skill['competency_ids'])) {
        $ids = array_map('intval', explode(',', $skill['competency_ids']));
    }
    return [
        'id' => (int) $skill['id'],
        'name' => $skill['name'],
        'competency_ids' => $ids,
    ];
}, $skills);

$pageTitle = 'Notes';
$currentNav = 'home';

include(__DIR__ . '/../includes/header.php');
?>
    <div class="dashboard-filters" id="dashboard-filters">
        <div class="app-field">
            <label for="filter-group">Groupe</label>
            <select id="filter-group">
                <option value="">Tous les groupes</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= (int) $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="app-field">
            <label for="filter-term">Étape</label>
            <select id="filter-term">
                <option value="">Toutes les étapes</option>
                <?php foreach ($terms as $term): ?>
                    <option value="<?= (int) $term['id'] ?>">Étape <?= htmlspecialchars($term['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="app-field">
            <label for="filter-competency">Compétence</label>
            <select id="filter-competency">
                <option value="">Toutes les compétences</option>
                <?php foreach ($competencies as $competency): ?>
                    <option value="<?= (int) $competency['id'] ?>"><?= htmlspecialchars($competency['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="app-field">
            <label for="filter-skill">Habileté</label>
            <select id="filter-skill">
                <option value="">Toutes les habiletés</option>
                <?php foreach ($skills as $skill): ?>
                    <option value="<?= (int) $skill['id'] ?>"><?= htmlspecialchars($skill['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="dashboard-content" class="dashboard-content" aria-live="polite">
        <p class="app-page-lead">Choisissez un groupe pour afficher le tableau.</p>
    </div>

    <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content note-modal">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="noteModalTitle">Notation</h2>
                        <p class="note-modal-meta mb-0" id="noteModalMeta"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" id="noteModalBody">
                    <p class="app-page-lead mb-0">Chargement…</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const skillsData = <?= json_encode($skillsForJs, JSON_UNESCAPED_UNICODE) ?>;
        const groupSelect = document.getElementById('filter-group');
        const termSelect = document.getElementById('filter-term');
        const competencySelect = document.getElementById('filter-competency');
        const skillSelect = document.getElementById('filter-skill');
        const contentEl = document.getElementById('dashboard-content');
        const modalEl = document.getElementById('noteModal');
        const modalTitle = document.getElementById('noteModalTitle');
        const modalMeta = document.getElementById('noteModalMeta');
        const modalBody = document.getElementById('noteModalBody');
        let modal = null;
        let loadTimer = null;
        let activeCell = null;

        function getModal() {
            if (!modal) {
                modal = new bootstrap.Modal(modalEl);
            }
            return modal;
        }

        function refreshSkillOptions() {
            const competencyId = parseInt(competencySelect.value || '0', 10);
            const currentSkill = skillSelect.value;
            skillSelect.innerHTML = '';

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'Toutes les habiletés';
            skillSelect.appendChild(emptyOption);

            let keepCurrent = false;
            skillsData.forEach(function (skill) {
                if (competencyId > 0 && skill.competency_ids.indexOf(competencyId) === -1) {
                    return;
                }
                const option = document.createElement('option');
                option.value = String(skill.id);
                option.textContent = skill.name;
                skillSelect.appendChild(option);
                if (String(skill.id) === currentSkill) {
                    keepCurrent = true;
                }
            });

            skillSelect.value = keepCurrent ? currentSkill : '';
        }

        function loadContent() {
            const params = new URLSearchParams({
                group_id: groupSelect.value || '',
                term_id: termSelect.value || '',
                competency_id: competencySelect.value || '',
                skill_id: skillSelect.value || '',
            });

            contentEl.classList.add('is-loading');
            contentEl.innerHTML = '<p class="app-page-lead">Chargement…</p>';

            fetch('/dashboard-content.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.text();
                })
                .then(function (html) {
                    contentEl.innerHTML = html;
                })
                .catch(function () {
                    contentEl.innerHTML = '<p class="app-page-lead">Impossible de charger le contenu.</p>';
                })
                .finally(function () {
                    contentEl.classList.remove('is-loading');
                });
        }

        function scheduleLoad() {
            clearTimeout(loadTimer);
            loadTimer = setTimeout(loadContent, 100);
        }

        function selectedValue(task) {
            if (!task.has_note) {
                return '';
            }
            if (task.is_ne || task.note === null) {
                return 'ne';
            }
            return String(task.note);
        }

        function displayAverage(average) {
            if (average === null || average === undefined) {
                return '—';
            }
            if (average === 'N/E') {
                return 'N/E';
            }
            return String(average);
        }

        function displayNote(note, cleared) {
            if (cleared) {
                return '—';
            }
            if (note === null) {
                return 'N/E';
            }
            if (note === undefined) {
                return '—';
            }
            return String(note);
        }

        function openNoteModal(cell) {
            activeCell = cell;
            const studentId = cell.dataset.studentId;
            const skillId = cell.dataset.skillId;
            const termId = cell.dataset.termId;
            const focusTaskId = cell.dataset.taskId || '';

            modalTitle.textContent = 'Notation';
            modalMeta.textContent = '';
            modalBody.innerHTML = '<p class="app-page-lead mb-0">Chargement…</p>';
            getModal().show();

            const params = new URLSearchParams({
                student_id: studentId,
                skill_id: skillId,
                term_id: termId,
            });

            fetch('/note-modal.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Erreur');
                    }
                    return response.json();
                })
                .then(function (data) {
                    const competencies = (data.skill.competencies || []).join(', ');
                    modalTitle.textContent = data.student.name + ' — ' + data.skill.name;
                    modalMeta.textContent = competencies
                        ? 'Compétences : ' + competencies
                        : 'Aucune compétence associée';

                    if (!data.tasks.length) {
                        modalBody.innerHTML = '<p class="app-page-lead mb-0">Aucune tâche pour cette habileté.</p>';
                        return;
                    }

                    let html = '<div class="note-modal-list">';
                    data.tasks.forEach(function (task) {
                        html += '<div class="note-modal-row' + (focusTaskId && String(task.task_id) === focusTaskId ? ' is-focused' : '') + '">';
                        html += '<div class="note-modal-task">';
                        html += '<div class="note-modal-task-name">' + escapeHtml(task.task_name) + '</div>';
                        html += '<div class="note-modal-task-skill">' + escapeHtml(data.skill.name);
                        if (competencies) {
                            html += ' <span class="note-modal-competencies">(' + escapeHtml(competencies) + ')</span>';
                        }
                        html += '</div></div>';
                        html += '<select class="note-modal-select" data-task-id="' + task.task_id + '" data-student-id="' + data.student.id + '" data-skill-id="' + data.skill.id + '" data-term-id="' + data.term_id + '">';
                        html += '<option value="">Choisir…</option>';
                        Object.keys(data.scale).forEach(function (key) {
                            const selected = selectedValue(task) === key ? ' selected' : '';
                            html += '<option value="' + key + '"' + selected + '>' + escapeHtml(data.scale[key]) + '</option>';
                        });
                        html += '</select></div>';
                    });
                    html += '</div>';
                    modalBody.innerHTML = html;
                })
                .catch(function () {
                    modalBody.innerHTML = '<p class="app-page-lead mb-0">Impossible de charger la notation.</p>';
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function saveNote(selectEl) {
            const body = new URLSearchParams({
                student_id: selectEl.dataset.studentId,
                task_id: selectEl.dataset.taskId,
                skill_id: selectEl.dataset.skillId,
                term_id: selectEl.dataset.termId,
                note: selectEl.value,
            });

            selectEl.disabled = true;

            fetch('/note-save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Erreur');
                    }
                    return response.json();
                })
                .then(function (data) {
                    // Update skill average cell
                    const skillCell = contentEl.querySelector(
                        '.note-cell[data-mode="skill"][data-student-id="' + data.student_id + '"][data-skill-id="' + data.skill_id + '"]'
                    );
                    if (skillCell) {
                        skillCell.textContent = displayAverage(data.average);
                    }

                    // Update task cell if present
                    const taskCell = contentEl.querySelector(
                        '.note-cell[data-mode="task"][data-student-id="' + data.student_id + '"][data-skill-id="' + data.skill_id + '"][data-task-id="' + data.task_id + '"]'
                    );
                    if (taskCell) {
                        taskCell.textContent = displayNote(data.note, data.cleared);
                    }

                    if (activeCell && activeCell.dataset.mode === 'skill') {
                        activeCell.textContent = displayAverage(data.average);
                    }
                    if (activeCell && activeCell.dataset.mode === 'task' && activeCell.dataset.taskId === String(data.task_id)) {
                        activeCell.textContent = displayNote(data.note, data.cleared);
                    }
                })
                .catch(function () {
                    alert('Impossible d’enregistrer la note.');
                })
                .finally(function () {
                    selectEl.disabled = false;
                });
        }

        groupSelect.addEventListener('change', scheduleLoad);
        termSelect.addEventListener('change', scheduleLoad);
        competencySelect.addEventListener('change', function () {
            refreshSkillOptions();
            scheduleLoad();
        });
        skillSelect.addEventListener('change', scheduleLoad);

        contentEl.addEventListener('click', function (event) {
            const cell = event.target.closest('.note-cell');
            if (!cell) {
                return;
            }
            openNoteModal(cell);
        });

        modalBody.addEventListener('change', function (event) {
            const selectEl = event.target.closest('.note-modal-select');
            if (!selectEl) {
                return;
            }
            saveNote(selectEl);
        });
    })();
    </script>
<?php

include(__DIR__ . '/../includes/footer.php');
