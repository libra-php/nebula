<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?= $meta ?? '' ?>
	<title><?= $title ?? 'Nebula' ?></title>
	<link href="css/main.css" rel="stylesheet">
	<?= $head ?? '' ?>
</head>

<body>
	<main>
		<?= $main ?? '' ?>
	</main>
	<script src="js/htmx.min.js"></script>
	<script src="js/main.js"></script>
	<?= $scripts ?? '' ?>
</body>

</html>
