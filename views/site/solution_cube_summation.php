<?php

foreach ($model->cases as $case) {
?>

<div class="row">
    <?php echo json_encode($case) ?>
</div>

<?php

}