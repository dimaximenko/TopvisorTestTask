<?php

require_once __DIR__ . '/vendor/autoload.php';

$googleAccountKeyFilePath = __DIR__ . '/service_credentials.json'; //ключ доступа к сервисному аккаунту
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleAccountKeyFilePath);

//Создаем новый клиент
$client = getClient();

$service = new Google_Service_Sheets($client);
$spreadsheetId = createTable($service);
//getAccessPermission($client, $spreadsheetId);
setAccessPermission($client, $spreadsheetId, 'test@gmail.com');

$data = [];
for ($i = 1; $i <= 10; $i++) {
    $data[0][] = $i;
}

setTableContent($service, $spreadsheetId, $data);

/*
 * Установим в таблице значения
 *
 */
function setTableContent($service, $spreadsheetId, $data) {
    $ValueRange = new Google_Service_Sheets_ValueRange();//диапазон значений
    $ValueRange->setMajorDimension('COLUMNS'); //указываем направление вставки - по столбцам
    $ValueRange->setValues($data); //устанавливаем наши данные
    $options = ['valueInputOption' => 'USER_ENTERED']; //указываем в опциях обрабатывать пользовательские данные

    //делаем запрос с указанием во втором параметре названия листа и начальную ячейку для заполнения
    $service->spreadsheets_values->update($spreadsheetId, 'Sheet1!A1', $ValueRange, $options);
}

/*
 * Создаем таблицу
 *
 */
function createTable($service) {
    try{
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => 'Topvisor Test Task'
            ]
        ]);
        $spreadsheet = $service->spreadsheets->create($spreadsheet);
        printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);
        printf("Spreadsheet URL: %s\n", $spreadsheet->spreadsheetUrl);

        return $spreadsheet->spreadsheetId;
    }
    catch(Exception $e) {
        echo 'Message: ' .$e->getMessage();
        return false;
    }
}

/*
 * Проверка прав доступа к таблице
 *
 */
function getAccessPermission($client, $spreadsheetId) {
    $drive = new Google_Service_Drive($client);
    $drivePermissions = $drive->permissions->listPermissions($spreadsheetId);
    foreach ($drivePermissions as $key => $value) {
        $role = $value->role;
        var_dump($role);
    }
}

/*
 * Установить разрешения на доступ к таблице
 *
 */
function setAccessPermission($client, $spreadsheetId, $email) {
    $drive = new Google_Service_Drive($client); //диск
    $drivePermisson = new Google_Service_Drive_Permission(); //разрешения диска
    $drivePermisson->setType('user'); //тип разрешения
    $drivePermisson->setEmailAddress($email); //указываем почту аккаунта которому нужно дать разрешение
    $drivePermisson->setRole('writer'); //права на редактирование

    // устанавливаем разрешение
    $drive->permissions->create($spreadsheetId, $drivePermisson);
}

/*
 * Создаем новый клиент
 *
 */
function getClient() {
    $client = new Google_Client();
    // Устанавливаем полномочия
    $client->useApplicationDefaultCredentials();
    // Добавляем область доступа к чтению, редактированию, созданию и удалению таблиц
    $client->addScope(['https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/spreadsheets']);
    return $client;
}