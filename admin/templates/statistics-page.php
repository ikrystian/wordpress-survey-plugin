<div class="wrap">
    <h1>Statystyki Ankiet</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th>Pytanie</th>
            <th>Liczba kliknięć</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?php echo esc_html($row->question_text); ?></td>
                <td><?php echo esc_html($row->click_count); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>