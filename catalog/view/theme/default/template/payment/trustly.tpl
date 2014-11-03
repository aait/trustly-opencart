<?php if (isset($warning)) { ?>
<div class="alert alert-warning"><?php echo $warning; ?></div>
<?php } else { ?>
    <?php if (isset($trustly_iframe_url)): ?>
        <iframe src="<?php echo $trustly_iframe_url; ?>" style="width:100%; height:600px; border:none;"></iframe>
    <?php endif; ?>
<?php } ?>
