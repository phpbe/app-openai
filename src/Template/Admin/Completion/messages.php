<be-head>

</be-head>


<be-page-content>
    <div class="be-bc-fff be-px-100 be-pt-100 be-pb-50">
        <?php
        foreach ($this->session->messages as $message) {
            echo '<div class="be-row">';
            echo '<div class="be-col-auto">';
            echo '<span class="be-c-major be-fw-bold">问：</span>';
            echo '</div>';
            echo '<div class="be-col be-c-major">';
            echo $message->question;
            echo '<span class="be-c-major-6">（' . $message->create_time . '）</span>';
            echo '</div>';
            echo '</div>';

            echo '<div class="be-row be-mt-50 be-mb-200">';
            echo '<div class="be-col-auto">';
            echo '<span class="be-fw-bold">签：</span>';
            echo '</div>';
            echo '<div class="be-col completion-session-message-answer">';
            echo $message->answer;
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</be-page-content>