<?php
$survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
$survey_title = $survey_id ? get_the_title($survey_id) : '';
$survey_questions = $survey_id ? get_post_meta($survey_id, 'survey_questions', true) : [];
?>

<div class="wrap">
    <h1>Generator Ankiety</h1>
    <form id="survey-form" data-survey-id="<?php echo $survey_id; ?>">
        <table class="form-table">
            <tr>
                <th>Tytuł Ankiety:</th>
                <td>
                    <input
                            type="text"
                            id="survey-title"
                            name="survey_title"
                            value="<?php echo esc_attr($survey_title); ?>"
                            required
                    >
                </td>
            </tr>

            <tr>
                <th>Pytania:</th>
                <td>
                    <div id="questions-container">
                        <?php if (!empty($survey_questions)): ?>
                            <?php foreach ($survey_questions as $index => $question): ?>
                                <div class="survey-question" data-question-index="<?php echo $index; ?>">
                                    <div class="question-header">
                                        <?php echo $index; ?>
                                        <input
                                                type="text"
                                                name="question_text"
                                                placeholder="Treść pytania"
                                                value="<?php echo esc_attr($question['question']); ?>"
                                                required
                                        >
                                        <button type="button" class="remove-question" title="Remove question">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                    <div class="answers-container">
                                        <?php foreach ($question['answers'] as $answer): ?>
                                            <div class="answer">
                                                <input
                                                        type="text"
                                                        name="answer_text"
                                                        placeholder="Odpowiedź"
                                                        value="<?php echo esc_attr($answer['text']); ?>"
                                                        required
                                                >
                                                <select name="answer_action">
                                                    <option
                                                            value="next_question"
                                                        <?php selected($answer['action'], 'next_question'); ?>
                                                    >
                                                        Przejdź do pytania
                                                    </option>
                                                    <option
                                                            value="redirect"
                                                        <?php selected($answer['action'], 'redirect'); ?>
                                                    >
                                                        Przekieruj
                                                    </option>
                                                    <option
                                                            value="message"
                                                        <?php selected($answer['action'], 'message'); ?>
                                                    >
                                                        Pokaż komunikat
                                                    </option>
                                                </select>
                                                <input
                                                        type="text"
                                                        name="action_value"
                                                        placeholder="ID pytania/URL/komunikat"
                                                        value="<?php echo esc_attr($answer['action_value']); ?>"
                                                >
                                                <button type="button" class="remove-answer">
                                                    <span class="dashicons dashicons-remove-question"></span>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="add-answer button button-secondary">Dodaj odpowiedź</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" id="add-question" class="button button-secondary">Dodaj Pytanie</button>
                </td>
            </tr>

        </table>


        <hr>

        <button type="submit" class="button button-primary">
            Zapisz Ankietę
        </button>
    </form>
</div>