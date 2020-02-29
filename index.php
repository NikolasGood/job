<?php
header("Content-type:text/html;charset=UTF-8");

include_once 'db_cfg.php';


// Ошибка API запроса
function error(){
	exit('{"result": "error"}');
}

// Роутер
function route($method, $urlData, $formData) {

	// GET /users/{user_id}/services/{service_id}/tarifs
	if ($method === 'GET' &&			// Метод
			count($urlData) === 5 &&	// Сколько параметров
			$urlData[0] === 'users' &&
			$urlData[2] === 'services' &&
			$urlData[4] === 'tarifs') {

		$user_id = $urlData[1];
		$service_id = $urlData[3];

		// Подключаемся к базе через PDO
		try {
			$dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;
			$pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
		}
		// Обрабатываем ошибку в PDO
		catch(PDOException $e) {
				exit('Подключение не удалось: ' . $e->getMessage());
		}


		// Какой id тарифа у пользователя
		$query = $pdo->query('SELECT tarif_id FROM `services` WHERE user_id = '.$user_id.' AND id = '.$service_id);
		if (!$query) error();
		$row = $query->fetch(PDO::FETCH_OBJ);
		$tarif_id = $row->tarif_id;


		// Информация о тарифе пользователя
		$query = $pdo->query('SELECT * FROM `tarifs` WHERE id = '.$tarif_id);
		if (!$query) error();
		$tarif = $query->fetch(PDO::FETCH_OBJ);
		$tarif_group = $tarif->tarif_group_id;


		// Информация о группе тарифов
		$query = $pdo->query('SELECT * FROM `tarifs` WHERE tarif_group_id = '.$tarif_group);
		if (!$query) error();
		$groups = json_encode(
			$query->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_SLASHES  | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
		);


		// Выводим ответ клиенту
		echo'
			{
				"result":"ok",
				"tarifs":[
					{
						"title":"'.$tarif->title.'",
						"link":"'.$tarif->link.'",
						"speed":'.$tarif->speed.',
						"tarifs":'.$groups.'
					}
				]
			}';

		return;
	}


	// PUT /users/{user_id}/services/{service_id}/tarif
	if ($method === 'PUT'						&& // Метод
			count($urlData) === 5 	 		&& // Сколько параметров
			$urlData[0] === 'users' 	 	&&
			$urlData[2] === 'services' 	&&
			$urlData[4] === 'tarif') {

		$user_id = $urlData[1];
		$service_id = $urlData[3];

		return;
	}


	// Возвращаем ошибку 404
	include('error_404.php');
}


// Получение данных из тела запроса
function getFormData($method) {

	// GET или POST: данные возвращаем как есть
	if ($method === 'GET') return $_GET;
	if ($method === 'POST') return $_POST;

	// PUT, PATCH или DELETE
	$data = array();
	$exploded = explode('&', file_get_contents('php://input'));

	foreach($exploded as $pair) {
		$item = explode('=', $pair);
		if (count($item) == 2) {
			$data[urldecode($item[0])] = urldecode($item[1]);
		}
	}

	return $data;
}

// Определяем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Получаем данные из тела запроса
$formData = getFormData($method);


// Разбираем url
$url = (isset($_GET['q'])) ? $_GET['q'] : '';
$url = rtrim($url, '/');
$urlData = explode('/', $url);


route($method, $urlData, $formData);