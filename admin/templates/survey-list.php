<div class="wrap">
    <h1 class="wp-heading-inline">Lista Ankiet</h1>
    <a href="?page=add-survey" class="page-title-action">Dodaj Nową Ankietę</a>
    <p>Lorem ipsum dolor sit amet</p>
    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th>Tytuł</th>
            <th>Skrót</th>
            <th>Data utworzenia</th>
            <th>Akcje</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($surveys as $survey): ?>
            <tr>
                <td><?php echo $survey->post_title; ?></td>
                <td>[survey id="<?php echo $survey->ID; ?>"]</td>
                <th><?php echo $survey->post_date; ?></th>
                <td>
                    <a href="?page=add-survey&survey_id=<?php echo $survey->ID; ?>"
                       class="button">Edytuj</a>
                    <form method="POST" action="">
                        <input type="hidden" name="delete_survey_id" value="<?php echo $survey->ID; ?>">
                        <input type="submit" value="Usuń ankietę" onclick="return confirm('Czy na pewno chcesz usunąć tę ankietę?');">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>