<?php

class SurveyShortcode
{
    public function __construct()
    {
        add_shortcode('survey', [$this, 'render_survey']);
    }

    public function render_survey($atts)
    {
        $survey_id = $atts['id'];
        $survey = get_post($survey_id);
        $questions = get_post_meta($survey_id, 'survey_questions', true);

        $user_id = $_COOKIE['user_id'];
        ob_start();
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_user_progress'; // Create a new table for user progress

        // Check if the user progress already exists
        $existing_progress = $wpdb->get_var($wpdb->prepare("SELECT progress FROM $table_name WHERE user_id = %s AND survey_id = %d", 0, $survey_id));
        if ($existing_progress) :
            echo "<h1>Nie mozesz drugi raz wypełnić ankiety <strong>chuju</strong></h1>";
        else   :
            ?>
            <div class="survey-container" data-survey-id="<?php echo $survey_id; ?>">
                <?php foreach ($questions

                as $index => $question): ?>
                <div
                        id="question-<?php echo $index; ?>"
                         <?php if (count($questions) == $index + 1): echo " data-last=true "; endif; ?>
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
                jQuery(document).ready(function ($) {

                    let progress = {"id": "<?php echo $survey_id; ?>", results: []}

                    var userId = '<?php echo esc_js($user_id); ?>'; // Przekazanie identyfikatora użytkownika

                    $('.survey-answer').on('click', function () {

                        localStorage.setItem('currentQuestionIndex', $(this).data('question-id'));
                        var questionId
                        if (localStorage.getItem('currentQuestionIndex')) {
                            questionId = localStorage.getItem('currentQuestionIndex');
                        } else {
                            questionId = $(this).data('question-id');
                        }
                        var question = $(this).parent().find('h3').text();
                        console.log(question);
                        var answerText = $(this).text();
                        var surveyId = <?php echo $survey_id; ?>;
                        var userId = '<?php echo esc_js($user_id); ?>'; // Przekazanie identyfikatora użytkownika
                        progress.results.push({question: question, answerText: answerText})
                        if ($(this).parent().data('last')) {
                            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                action: 'save_user_progress',
                                user_id: userId,
                                survey_id: surveyId,
                                progress_data: progress
                            }, {});
                        }
                    });
                });
            </script>
            <?php
        endif;
            return ob_get_clean();
        }
}