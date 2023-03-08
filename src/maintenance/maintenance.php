<?php

class MaintenancePanel implements Tracy\IBarPanel
{
	public function getTab(): string
	{
		return '<span style="background-color: red; color: white; font-weight: bold; padding: 2px">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
</svg>

	<span class="tracy-label">Maintenance mode ON</span>
	<script defer>
   const body = document.querySelector(\'body\');
   const maintananceEl = document.createElement(\'div\');
   maintananceEl.style.color = \'white\';
   maintananceEl.style.backgroundColor = \'red\';
   maintananceEl.style.textAlign = \'center\';
   maintananceEl.style.padding = \'5px 10px\';
   maintananceEl.style.fontWeight = \'bold\';
   maintananceEl.textContent = \'Maintenance mode ON\';

   body.prepend(maintananceEl);
</script>
</span>';
	}

	public function getPanel()
	{
		return '';
	}
}

Tracy\Debugger::getBar()->addPanel(new MaintenancePanel());

/** @var \Nette\DI\Container $container */
if ($container->getParameters()['debugMode']) {
	/** @var \Base\Application $application */
	$application->run();

	exit;
}

/** @var \Nette\Http\Request $request */
$request = $container->getByType(\Nette\Http\Request::class);

header('HTTP/1.1 503 Service Unavailable');
// 5 minutes in seconds
header('Retry-After: 300');

$lang = $request->detectLanguage(['cs', 'sk']) ?? 'en';

$czOrSk = $lang === 'cs' || $lang === 'sk';

$title = $czOrSk ? 'Na stránce právě probíhají aktualizace' : 'Site is under maintenance';
$heading = $title;
$text = $czOrSk
	? 'Omlouváme se za vzniklé nepříjemnosti, právě pracujeme na aktualizacích a proto bude stránka na pár minut nedostupná.'
	: "Sorry for inconvenience. We're performing maintenance at the moment. Site will be unavailable for few minutes.";

?>
	<!DOCTYPE html>
	<html lang="<?=$lang?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width">
		<meta name=robots content=noindex>
		<meta name=generator content='Nette Framework'>

		<title><?=$title?></title>

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700;800&display=swap" rel="stylesheet">

		<style>
			* {
				box-sizing: border-box;
			}

			html {
				background: rgb(242,242,242);
				background: linear-gradient(180deg, rgba(242,242,242,1) 0%, rgba(255,255,255,1) 100%);
			}

			body {
				font-family: 'Open Sans', 'Roboto', sans-serif;
				line-height: 1.5;
				padding: 0;
				margin: 0;
			}

			h1 {
				font-size: 32px;
				font-weight: 800;
				line-height: 1.15;
			}

			p {
				font-size: 20px;
				font-weight: 300;
				margin-top: 0;
			}

			.container {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				height: 100vh;
				max-width: 900px;
				margin: 0 auto;
				padding: 0 15px;
				text-align: center;
			}

			@media (min-width: 992px) {
				h1 {
					font-size: 72px;
				}

				p {
					font-size: 32px;
				}
			}
		</style>
	</head>

	<body>

	<div class="container">
		<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-tools" viewBox="0 0 16 16" style="color: #bdbdbd;">
			<path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.878-.851l2.654-2.617.968.968-.305.914a1 1 0 0 0 .242 1.023l3.27 3.27a.997.997 0 0 0 1.414 0l1.586-1.586a.997.997 0 0 0 0-1.414l-3.27-3.27a1 1 0 0 0-1.023-.242L10.5 9.5l-.96-.96 2.68-2.643A3.005 3.005 0 0 0 16 3c0-.269-.035-.53-.102-.777l-2.14 2.141L12 4l-.364-1.757L13.777.102a3 3 0 0 0-3.675 3.68L7.462 6.46 4.793 3.793a1 1 0 0 1-.293-.707v-.071a1 1 0 0 0-.419-.814L1 0Zm9.646 10.646a.5.5 0 0 1 .708 0l2.914 2.915a.5.5 0 0 1-.707.707l-2.915-2.914a.5.5 0 0 1 0-.708ZM3 11l.471.242.529.026.287.445.445.287.026.529L5 13l-.242.471-.026.529-.445.287-.287.445-.529.026L3 15l-.471-.242L2 14.732l-.287-.445L1.268 14l-.026-.529L1 13l.242-.471.026-.529.445-.287.287-.445.529-.026L3 11Z"/>
		</svg>

		<h1><?=$heading?></h1>

		<p><?=$text?></p>
	</div>

	</body>
	</html>
<?php

exit;
