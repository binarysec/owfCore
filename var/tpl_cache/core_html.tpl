<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title><?php echo $t->vars['html_title']; ?></title>
<?php echo $t->vars['html_meta'];  echo $t->vars['css'];  echo $t->vars['javascript']; ?>
</head>

<body<?php echo $t->vars['body_attribs']; ?>>

<?php echo $t->vars['html_body']; ?>

<?php if($t->vars['html_managed_body']): echo $t->vars['html_managed_body'];  endif;?>
</body>
</html>
