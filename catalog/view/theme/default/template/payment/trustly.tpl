<h2><?php echo $text_title; ?></h2>
<?php if (isset($trustly_iframe_url)): ?>
    <iframe src="<?php echo $trustly_iframe_url; ?>" style="width:100%; height:600px; border:none;"></iframe>
<?php endif; ?>
