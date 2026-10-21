(() => {
    const form = document.getElementById('teacher-activity-form');
    if (!form) return;

    const schoolSelect = document.getElementById('target-school');
    const seriesSelect = document.getElementById('target-serie');
    const questionList = document.getElementById('math-question-list');
    const questionTemplate = document.getElementById('math-question-template');
    const addQuestionButton = document.getElementById('add-math-question');
    const titleInput = form.querySelector('[name="title"]');
    const schoolClasses = JSON.parse(schoolSelect.dataset.schoolClasses || '{}');
    const operationSymbols = {
        addition: '+',
        subtraction: '−',
        multiplication: '×',
        division: '÷',
    };

    function updateSeriesOptions(selectedValue = '') {
        const series = schoolClasses[schoolSelect.value] || [];
        seriesSelect.replaceChildren(new Option(
            schoolSelect.value ? 'Selecione a turma / série' : 'Selecione primeiro a escola',
            ''
        ));
        series.forEach((value) => {
            const option = new Option(value, value, false, value === selectedValue);
            seriesSelect.add(option);
        });
        seriesSelect.disabled = series.length === 0;
        updateActivityPreview();
    }

    function updateNumbers() {
        const questions = questionList.querySelectorAll('[data-math-question]');
        questions.forEach((question, index) => {
            const title = question.querySelector('.teacher-math-question-title');
            const remove = question.querySelector('[data-remove-question]');
            title.textContent = `Questão ${index + 1}`;
            remove.setAttribute('aria-label', `Remover questão ${index + 1}`);
            const first = question.querySelector('[name="questions[first][]"]').value.trim();
            const operation = question.querySelector('[name="questions[operation][]"]').value;
            const second = question.querySelector('[name="questions[second][]"]').value.trim();
            const answer = question.querySelector('[name="questions[answer][]"]').value.trim();
            const preview = question.querySelector('[data-question-preview]');
            const symbol = operationSymbols[operation];
            preview.textContent = first && second
                ? `Quanto é ${first} ${symbol} ${second}?${answer ? ` Resultado informado: ${answer}.` : ''}`
                : 'Prévia da operação aparecerá aqui.';
        });
        addQuestionButton.disabled = questions.length >= 50;
        document.querySelector('[data-activity-preview-count]').textContent =
            `${questions.length} ${questions.length === 1 ? 'questão' : 'questões'}`;
        updateActivityPreview();
    }

    function updateActivityPreview() {
        const title = titleInput.value.trim();
        document.querySelector('[data-activity-preview-title]').textContent = title || 'Título da atividade';
        const school = schoolSelect.selectedOptions[0]?.textContent || '';
        const series = seriesSelect.selectedOptions[0]?.textContent || '';
        document.querySelector('[data-activity-preview-target]').textContent =
            school && schoolSelect.value && series && seriesSelect.value
                ? `Destino: ${school} · ${series}`
                : 'Selecione a escola e a turma de destino.';
    }

    schoolSelect.addEventListener('change', () => updateSeriesOptions());
    seriesSelect.addEventListener('change', updateActivityPreview);
    titleInput.addEventListener('input', updateActivityPreview);
    addQuestionButton.addEventListener('click', () => {
        if (questionList.querySelectorAll('[data-math-question]').length >= 50) return;
        questionList.append(questionTemplate.content.cloneNode(true));
        updateNumbers();
        questionList.lastElementChild.querySelector('input').focus();
    });
    questionList.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-question]');
        if (!removeButton) return;
        removeButton.closest('[data-math-question]').remove();
        updateNumbers();
    });
    questionList.addEventListener('input', updateNumbers);
    questionList.addEventListener('change', updateNumbers);

    updateNumbers();
    updateSeriesOptions(seriesSelect.value);
})();
