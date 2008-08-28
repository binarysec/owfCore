<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title><?php echo $t->vars['html_title']; ?></title>
<?php echo $t->vars['html_meta']; ?>

<?php if($t->vars['html_css']): foreach($t->vars['html_css'] as $t->vars['k'] => $t->vars['v']):?> 
<link rel="stylesheet" type="text/css" href="<?php echo $t->vars['v'][1]; ?>"/>
<?php endforeach;?>

<?php endif; if($t->vars['html_js']): foreach($t->vars['html_js'] as $t->vars['k'] => $t->vars['v']):?> 
<script type="text/javascript" src="<?php echo $t->vars['v'][1]; ?>"></script>
<?php endforeach; endif;?>

</head>

<body<?php echo $t->vars['body_attribs']; ?>>

<?php echo $t->vars['html_body']; ?>

<?php if($t->vars['html_managed_body']): echo $t->vars['html_managed_body'];  endif;?>
</body>
</html>
