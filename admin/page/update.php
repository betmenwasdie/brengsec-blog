<?php
#===============================================================================
# DEFINE: Administration
#===============================================================================
define('ADMINISTRATION', TRUE);
define('AUTHENTICATION', TRUE);

#===============================================================================
# INCLUDE: Main configuration
#===============================================================================
require '../../core/application.php';

#===============================================================================
# TRY: Page\Exception
#===============================================================================
try {
	$Page = Page\Factory::build(HTTP::GET('id'));
	$Attribute = $Page->getAttribute();

	if(HTTP::issetPOST('user', 'slug', 'name', 'body', 'argv', 'time_insert', 'time_update', 'update')) {
		$Attribute->set('user', HTTP::POST('user'));
		$Attribute->set('slug', HTTP::POST('slug') ? HTTP::POST('slug') : makeSlugURL(HTTP::POST('name')));
		$Attribute->set('name', HTTP::POST('name') ? HTTP::POST('name') : NULL);
		$Attribute->set('body', HTTP::POST('body') ? HTTP::POST('body') : NULL);
		$Attribute->set('argv', HTTP::POST('argv') ? HTTP::POST('argv') : NULL);
		$Attribute->set('time_insert', HTTP::POST('time_insert') ? HTTP::POST('time_insert') : date('Y-m-d H:i:s'));
		$Attribute->set('time_update', HTTP::POST('time_update') ? HTTP::POST('time_update') : date('Y-m-d H:i:s'));

		if(HTTP::issetPOST(['token' => Application::getSecurityToken()])) {
			try {
				$Attribute->databaseUPDATE($Database);
			} catch(PDOException $Exception) {
				$messages[] = $Exception->getMessage();
			}
		}

		else {
			$messages[] = $Language->text('error_security_csrf');
		}
	}

	#===============================================================================
	# TRY: Template\Exception
	#===============================================================================
	try {
		$userIDs = $Database->query(sprintf('SELECT id FROM %s ORDER BY fullname ASC', User\Attribute::TABLE));

		foreach($userIDs->fetchAll($Database::FETCH_COLUMN) as $userID) {
			$User = User\Factory::build($userID);
			$userAttributes[] = [
				'ID' => $User->attr('id'),
				'FULLNAME' => $User->attr('fullname'),
				'USERNAME' => $User->attr('username'),
			];
		}

		$FormTemplate = Template\Factory::build('page/form');
		$FormTemplate->set('FORM', [
			'TYPE' => 'UPDATE',
			'INFO' => $messages ?? [],
			'DATA' => [
				'ID'   => $Attribute->get('id'),
				'USER' => $Attribute->get('user'),
				'SLUG' => $Attribute->get('slug'),
				'NAME' => $Attribute->get('name'),
				'BODY' => $Attribute->get('body'),
				'ARGV' => $Attribute->get('argv'),
				'TIME_INSERT' => $Attribute->get('time_insert'),
				'TIME_UPDATE' => $Attribute->get('time_update'),
			],
			'USER_LIST' => $userAttributes ??  [],
			'TOKEN' => Application::getSecurityToken()
		]);

		$PageUpdateTemplate = Template\Factory::build('page/update');
		$PageUpdateTemplate->set('HTML', $FormTemplate);

		$MainTemplate = Template\Factory::build('main');
		$MainTemplate->set('NAME', $Language->text('title_page_update'));
		$MainTemplate->set('HTML', $PageUpdateTemplate);
		echo $MainTemplate;
	}

	#===============================================================================
	# CATCH: Template\Exception
	#===============================================================================
	catch(Template\Exception $Exception) {
		Application::exit($Exception->getMessage());
	}
}

#===============================================================================
# CATCH: Page\Exception
#===============================================================================
catch(Page\Exception $Exception) {
	Application::error404();
}
?>