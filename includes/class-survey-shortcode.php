<?php
class SurveyShortcode {
    public function __construct() {
        add_shortcode('survey', [$this, 'render_survey']);
    }

    public function render_survey($atts) {
        $survey_id = $atts['id'];
        $survey = get_post($survey_id);
        $questions = get_post_meta($survey_id, 'survey_questions', true);

        ob_start();
        ?>
        <div class="survey-container" data-survey-id="<?php echo $survey_id; ?>">
            <?php foreach ($questions as $index => $question): ?>
            <div
                    id="question-<?php echo $index; ?>"
                    class="survey-question"
                    data-question-index="<?php echo $index; ?>"
            >
                <h3><?php echo esc_html($question['question']); ?></h3>
                <?php foreach ($question['answers'] as $answer): ?>
                <div
                        class="survey-answer"
                        data-action="<?php echo $answer['action']; ?>"
                        data-action-value="<?php echo $answer['action_value']; ?>"
                        data-question-id="<?php echo $index; ?>" <!-- Dodajemy ID pytania -->
                >
                <?php echo esc_html($answer['text']); ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('.survey-answer').on('click', function() {
                    var questionId = $(this).data('question-id');
                    var answerText = $(this).text();
                    var surveyId = <?php echo $survey_id; ?>;

                    // Wysyłanie danych do serwera
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'record_click',
                        survey_id: surveyId,
                        question_id: questionId,
                        answer_text: answerText,
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
}