jQuery(document).ready(function($) {
    if ($('#survey-form').length) {
        let questionIndex = $('.survey-question').length || 0;

        $('#add-question').on('click', function() {
            const newQuestion = createQuestionHTML(questionIndex);
            $('#questions-container').append(newQuestion);
            questionIndex++;
        });

        $('.question-header').on('click', function() {
            $(this).siblings('.answers-container').slideToggle();
        })

        $('#questions-container').on('click', '.remove-question', function() {
            $(this).closest('.survey-question').remove();
        });

        $('#questions-container').on('click', '.add-answer', function() {
            const $answersContainer = $(this).siblings('.answers-container');
            const newAnswer = createAnswerHTML();
            $answersContainer.append(newAnswer);

            $.get('http://quiz.local/wp-json/wp/v2/pages', function(data) {
                $.each(data, function(index, page) {
                    $('.page-select:not(empty)').append($('<option>', {
                        value: page.id,
                        text: page.title.rendered
                    }));
                });
            });
        });

        $('#questions-container').on('click', '.remove-answer', function() {
            $(this).closest('.answer').remove();
        });

        $('#survey-form').on('submit', function(e) {
            e.preventDefault();

            const surveyData = collectSurveyData();
            const surveyTitle = $('#survey-title').val();
            const surveyId = $(this).data('survey-id') || 0;

            $.ajax({
                url: surveyAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'save_survey',
                    nonce: surveyAjax.nonce,
                    survey_data: JSON.stringify(surveyData),
                    survey_title: surveyTitle,
                    survey_id: surveyId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Ankieta została zapisana');
                        window.location.href = 'admin.php?page=survey-generator';
                    } else {
                        alert('Błąd zapisu: ' + response.data);
                    }
                }
            });
        });

        // Usuwanie ankiety w liście
        $('.delete-survey').on('click', function() {
            const surveyId = $(this).data('survey-id');

            if (confirm('Czy na pewno chcesz usunąć tę ankietę?')) {
                $.ajax({
                    url: surveyAjax.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'delete_survey',
                        nonce: surveyAjax.nonce,
                        survey_id: surveyId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Nie udało się usunąć ankiety');
                        }
                    }
                });
            }
        });

        function createQuestionHTML(index) {
            return `
                <div class="survey-question" data-question-index="${index}">
                    <div class="question-header">
                        <input type="text" name="question_text" placeholder="Treść pytania #${index + 1}" required>
                        <button type="button" class="remove-question" title="Remove question">
                        <span class="dashicons dashicons-trash"></span></button>
                    </div>
                    <div class="answers-container">
                        ${createAnswerHTML()}
                    </div>
                    <button type="button" class="add-answer button button-secondary">Dodaj odpowiedź</button>
                </div>
            `;
        }


        function createAnswerHTML() {
            return `
                <div class="answer">
                    <input type="text" name="answer_text" placeholder="Odpowiedź" required>
                    <select name="answer_action">
                        <option value="finnish">Zakończ ankietę</option>
                        <option value="next_question">Przejdź do pytania</option>
                        <option value="redirect">Przekieruj</option>
                        <option value="message">Pokaż komunikat</option>
                    </select>
                    <select name=""  class="page-select"></select>
                    <input 
                        type="text" 
                        name="action_value" 
                        placeholder="ID pytania/URL/komunikat"
                    >
                    <button type="button" class="remove-answer" title="Remove answer"><span class="dashicons dashicons-trash"></span></button>
                </div>
            `;
        }
        progress = {id: "progress"}
        function collectSurveyData() {
            const surveyData = [];

            $('.survey-question').each(function(questionIndex) {
                const questionText = $(this).find('input[name="question_text"]').val();
                const answers = [];

                $(this).find('.answer').each(function() {
                    const answerText = $(this).find('input[name="answer_text"]').val();
                    const action = $(this).find('select[name="answer_action"]').val();
                    const actionValue = $(this).find('input[name="action_value"]').val();

                    answers.push({
                        text: answerText,
                        action: action,
                        action_value: actionValue,
                        next_question: action === 'next_question' ? actionValue : null
                    });
                });

                surveyData.push({
                    question: questionText,
                    answers: answers
                });
            });

            return surveyData;
        }
    }

    // Sekcja Frontend
    if ($('.survey-container').length) {
        let currentQuestionIndex = 0; // Odczytanie indeksu z localStorage
        const $questions = $('.survey-question');
        const $surveyContainer = $('.survey-container');
        showNextQuestion(parseInt(currentQuestionIndex) + 1)
        $questions.find(currentQuestionIndex).addClass('active');
        $('.survey-answer').on('click', function() {

            const action = $(this).data('action');
            const actionValue = $(this).data('action-value');

            switch(action) {
                case 'next_question':
                    showNextQuestion(actionValue);
                    break;
                case 'redirect':
                    window.location.href = actionValue;
                    break;
                case 'message':
                    showMessage(actionValue);
                    break;
            }
        });

        function showNextQuestion(nextQuestionId) {
            $questions.removeClass('active');
            $(`#question-${nextQuestionId}`).addClass('active');

        }

        function showMessage(message) {
            $surveyContainer.html(`<div class="survey-message">${message}</div>`);
        }
    }
});


function createAnswerHTML() {
    return `
        <div class="answer">
            <input type="text" name="answer_text" placeholder="Odpowiedź" required>
            <select name="answer_action">
                <option value="finish">Zakończ ankietę</option>
                <option value="next_question">Przejdź do pytania</option>
                <option value="redirect">Przekieruj</option>
                <option value="message">Pokaż komunikat</option>
            </select>
                                <select name=""  class="page-select"></select>

            <input 
                type="text" 
                name="action_value" 
                placeholder="ID pytania/URL/komunikat"
            >

                

            <button type="button" class="remove-answer">×</button>
        </div>
    `;
}
