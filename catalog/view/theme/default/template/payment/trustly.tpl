<?php if (isset($warning)) { ?>
<div class="warning"><?php echo $warning; ?></div>
<?php } else { ?>
    <h2><?php echo $text_title; ?></h2>
    <?php if (isset($trustly_iframe_url)) { ?>
        <iframe src="<?php echo $trustly_iframe_url; ?>" style="width:100%; height:600px; border:none;"></iframe>
    <?php } ?>
<?php } ?>

