<?php
namespace app\controllers;
//use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\models\Resource;
use app\models\PersonalData;
use app\models\ResourceClass;
use app\models\Parameter;
use app\models\ResourceAttribute;
use app\models\Coordinates;
use app\models\User;
use app\models\Community;
use app\models\Request;

class ResourceController extends AppController
{
	public $modelClass = 'app\models\Resource';
	

	public function actionSearch()
	{
		$get = \Yii::$app->request->get();
		$model = new $this->modelClass;
		$query = $model::find();

		$query->andFilterWhere(['like', 'name', $get['name']]);
		$query->andFilterWhere(['like', 'class_id', $get['class_id']]);
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize' => 4,
			],
		]);

		return $dataProvider;
	}

	public function actionGetregisterkey()
	{
		$get = \Yii::$app->request->get();
		$model = new $this->modelClass;
		$query = $model::find();

		$query->andFilterWhere(['registrar_data_id' => $get['registrar_data_id']]);
		$query->orderBy('registration_number DESC')->one();

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize' => 1,
			],
		]);

		return $dataProvider;
	}

	public function actionExport($id)
	{
		$templateFilepath = dirname(__FILE__) . '/../templates/Template.docx';
		$source = dirname(__FILE__) . '/../temp.docx';

		\PhpOffice\PhpWord\Autoloader::register();

		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateFilepath);

		$months = ['Січня', 'Лютого', 'Березня', 'Квітня',
			'Травня', 'Червня', 'Липня', 'Серпня', 'Вересня',
			'Жовтня', 'Листопада', 'Грудня'];
		$date = getdate();

		$currentDate = $date['mday'] . ' ' . $months[$date['mon'] - 1] . ' ' . $date['year'] . ' року';

		$extractNumber = '№' . $date['mday'] . ($date['mon'] - 1) . substr($date['year'], -2);

		$templateProcessor->setValue('date', $currentDate);
		$templateProcessor->setValue('number', $extractNumber);
		$templateProcessor->saveAs($source);

		$phpWord = \PhpOffice\PhpWord\IOFactory::load($source);

		$phpWord->setDefaultFontName('Times New Roman');
		$phpWord->setDefaultFontSize(11);

		$sectionStyle = [
			'marginTop' => 1000
		];
		$tableStyle = [
			'borderSize' => 6,
			'borderColor' => '000',
			'cellMargin' => 0,
		];
		$innerTableStyle = [
			'cellMargin' => 20,
		];
		$boldFontStyle = ['bold' => true];
		$italicFontStyle = ['italic' => true];
		$styleCell = ['valign' => 'center'];
		$styleCellBTLR = ['valign' => 'center', 'textDirection' => \PhpOffice\PhpWord\Style\Cell::TEXT_DIR_BTLR];
		$innerTableCellStyle = [
			'borderRightSize' => 6,
			'borderRightColor' => '000',
			'borderLeftSize' => 6,
			'borderLeftColor' => '000',
			'borderBottomSize' => 6,
			'borderBottomColor' => '000',
			'width' => 500
		];
		$innerTableRightCellStyle = [
			'borderLeftSize' => 6,
			'borderLeftColor' => '000',
			'borderBottomSize' => 6,
			'borderBottomColor' => '000',
			'width' => 500
		];
		$innerTableFontStyle = [
			'size' => 9
		];
		$innerTableParagraphStyle = [
			'align' => 'center'
		];

		$resource = Resource::findOne($id);

		$filename = $resource->name. '.docx';

		$coordinates = json_decode($resource->coordinates);

		$owner_name = 'народ України (Український народ)';

		$resource_class = 'природний ресурс';
		$resource_subclass = ResourceClass::findOne($resource->class_id)->name;
		$creation_date = $resource->date;

		$registrar = PersonalData::findOne($resource->registrar_data_id);
		$registrar_info = $registrar->last_name . ' ' .
		    $registrar->first_name . ' ' . $registrar->middle_name. ' ' .
		    $registrar->address;
		$registrar_shortname =  $registrar->last_name . ' ' .
		    $registrar->first_name . ' ' . $registrar->middle_name. ' ';
		$registration_number = $resource->registration_number;

		$parameters = Parameter::find()
			->where(['resource_id' => $id])
			->all();

		$attributes = [];

		foreach ($parameters as $parameter) {
			$parameter_name = ResourceAttribute::findOne($parameter->attribute_id);
			$attributes[$parameter_name->name] = $parameter->value;
		}

		$length = $attributes['length'];
		$width = $attributes['width'];
		$height = $attributes['height'];

		if ($length || $width || $height) {
			if (!$length) {
				$length = '0';
			}
			if (!$width) {
				$width = '0';
			}
			if (!$height) {
				$height = '0';
			}
			$attributes['linear_size'] = $length . ':' . $width . ':' . $height;
		}

		$reason = $resource->reason;

		function formatCoords($num)
		{
			$num = round($num, 4, PHP_ROUND_HALF_DOWN);
			$degrees = floor($num);
			$minfloat = ($num - $degrees) * 60;
			$minutes = floor($minfloat);
			$secfloat = ($minfloat - $minutes) * 60;
			$seconds = round($secfloat);
			if ($seconds == 60) {
				$minutes++;
				$seconds = 0;
			}
			if ($minutes == 60) {
				$degrees++;
				$minutes = 0;
			}

			return (string)$degrees . '°' . (string)$minutes . '\'' . (string)$seconds . '"';
		}

		$tableFields = [
			'Найменування об’єкту' => $resource->name,
			'Клас об’єкту' => $resource_class,
			'Підклас об’єкту' => $resource_subclass,
			'Власник об’єкту' => $owner_name,
			'Географічні координати кутів (вершин) об’єкту у форматі ГГ°ММ\'СС,СС". ' => $coordinates,
			'Лінійні розміри об’єкту, Д:Ш:В, м' => $attributes['linear_size'] ? $attributes['linear_size'] . ' м' : '',
			'Загальна площа об’єкту, га' => $attributes['square'] ? ($attributes['square'] / 10000) . ' га' : '',
			'Маса (вага) об’єкту, кг' => $attributes['weight'] ? ($attributes['weight'] . ' кг') : '',
			'Периметр об’єкту, м' => $attributes['perimeter'] ? $attributes['perimeter'] . ' м' : '',
			'Об’єм об’єкту, м3' => $attributes['volume'] ? $attributes['volume'] . ' м³' : '',
			'Підстава для внесення відомостей до Реєстру' =>  $reason,
			'ПІБ та поштова адреса народного реєстратора' => $registrar_info,
			'Реєстраційний номер об’єкту' => $registration_number,
			'Дата створення запису' => $creation_date ? str_replace('-', '.', $creation_date) . ' року' : '',
		];
		$tableUnitalicFields = [
			'Клас об’єкту',
			'Власник об’єкту'
		];

		$sections = $phpWord->getSections();
		$section = $sections[0];

		$phpWord->addTableStyle('Resource Table', $tableStyle);
		$table = $section->addTable('Resource Table');

		foreach ($tableFields as $key => $value) {
			if ($value) {
				if (!is_array($value)) {
					$valueFontStyle = [];
					if (in_array($key, $tableUnitalicFields)) {
						$valueFontStyle = $italicFontStyle;
					}
					$table->addRow(200);
					$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(4))
						->addText(htmlspecialchars($key, ENT_COMPAT, 'UTF-8'), $boldFontStyle);
					$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(12))
						->addText(htmlspecialchars($value, ENT_COMPAT, 'UTF-8'), $valueFontStyle);
				} else {
					$row = $table->addRow();
					$row->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))
						->addText(htmlspecialchars($key, ENT_COMPAT, 'UTF-8'), $boldFontStyle);
					$cell = $row->addCell();
					$innerTable = $cell->addTable($innerTableStyle);

					$innerTable->addRow(10);
					$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2), $innerTableCellStyle)
						->addText(htmlspecialchars('Північна широта', ENT_COMPAT, 'UTF-8'), $innerTableFontStyle, $innerTableParagraphStyle);
					$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2), $innerTableCellStyle)
						->addText(htmlspecialchars('Східна довгота', ENT_COMPAT, 'UTF-8'), $innerTableFontStyle, $innerTableParagraphStyle);
					$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3), $innerTableCellStyle)
						->addText(htmlspecialchars("Північна широта \n(продовження)", ENT_COMPAT, 'UTF-8'), $innerTableFontStyle, $innerTableParagraphStyle);
					$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3), $innerTableRightCellStyle)
						->addText(htmlspecialchars("Східна довгота \n(продовження)", ENT_COMPAT, 'UTF-8'), $innerTableFontStyle, $innerTableParagraphStyle);

					$coordinatesNumber = count($coordinates);
					for ($i=1; $i <= round($coordinatesNumber / 2); $i++)
					{
						$lat = '';
						$lng = '';
						$latCont = '';
						$lngCont = '';
						if ($coordinatesNumber >= $i)
						{
							$lat = formatCoords($coordinates[$i - 1][0]);
							$lng = formatCoords($coordinates[$i - 1][1]);
						}
						if ($coordinatesNumber >= (round($coordinatesNumber / 2) + $i))
						{
							$latCont = formatCoords($coordinates[$i + round($coordinatesNumber / 2) - 1][0]);
							$lngCont = formatCoords($coordinates[$i + round($coordinatesNumber / 2) - 1][1]);
						}
						$innerTable->addRow(10);
						$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2), $innerTableCellStyle)
							->addText(htmlspecialchars($lat, ENT_COMPAT, 'UTF-8'), $italicFontStyle);
						$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2), $innerTableCellStyle)
							->addText(htmlspecialchars($lng, ENT_COMPAT, 'UTF-8'), $italicFontStyle);
						$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2), $innerTableCellStyle)
							->addText(htmlspecialchars($latCont, ENT_COMPAT, 'UTF-8'), $italicFontStyle);
						$innerTable->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2), $innerTableRightCellStyle)
							->addText(htmlspecialchars($lngCont, ENT_COMPAT, 'UTF-8'), $italicFontStyle);
					}
				}
			}
		}


		$section->addTextBreak(2);
		$section->addText('Народний реєстратор', $boldFontStyle);
		$section->addText(htmlspecialchars($registrar_shortname, ENT_COMPAT, 'UTF-8'));

		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$xmlWriter->save("php://output");
	}

	public function actionExtract($id)
	{
		$templateFilepath = dirname(__FILE__) . '/../templates/Template1.docx';
		$source = dirname(__FILE__) . '/../temp1.docx';

		\PhpOffice\PhpWord\Autoloader::register();

		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templateFilepath);

		$months = ['Січня', 'Лютого', 'Березня', 'Квітня',
			'Травня', 'Червня', 'Липня', 'Серпня', 'Вересня',
			'Жовтня', 'Листопада', 'Грудня'];
		$date = getdate();

		$currentDate = $date['mday'] . ' ' . $months[$date['mon'] - 1] . ' ' . $date['year'] . ' року';

		$extractNumber = '№' . $date['mday'] . ($date['mon'] - 1) . substr($date['year'], -2);

		$resource = Resource::findOne($id);
		$registrar = PersonalData::findOne($resource->registrar_data_id);
		$registrar_info = $registrar->last_name . ' ' .
		    $registrar->first_name . ' ' . $registrar->middle_name. ' ' .
		    $registrar->address;
		$registrarshortname =  $registrar->last_name . ' ' .
		    $registrar->first_name . ' ' . $registrar->middle_name. ' ';
		$registration_number = $resource->registration_number;
		$registraraddress = $registrar->address;

		$usernameOf = \Yii::$app->user->identity->username;
        $username2Of = $resource->registration_number;

		$templateProcessor->setValue('date', $currentDate);
		$templateProcessor->setValue('name', $usernameOf);
		$templateProcessor->setValue('address', $registrar_info);
		$templateProcessor->setValue('registrar', $username2Of);
		$templateProcessor->setValue('registraraddress', '79066, м.Львів, вул.Зелена 320');


		$templateProcessor->setValue('resnumber', '804:23:17:026:0021');
		$templateProcessor->saveAs($source);

		$phpWord = \PhpOffice\PhpWord\IOFactory::load($source);

		$phpWord->setDefaultFontName('Times New Roman');
		$phpWord->setDefaultFontSize(11);

		$sectionStyle = [
			'marginTop' => 1000
		];
		$tableStyle = [
			'borderSize' => 6,
			'borderColor' => '000',
			'cellMargin' => 0,
		];
		$innerTableStyle = [
			'cellMargin' => 20,
		];
		$boldFontStyle = ['bold' => true];
		$italicFontStyle = ['italic' => true];
		$styleCell = ['valign' => 'center'];
		$styleCellBTLR = ['valign' => 'center', 'textDirection' => \PhpOffice\PhpWord\Style\Cell::TEXT_DIR_BTLR];
		$innerTableCellStyle = [
			'borderRightSize' => 6,
			'borderRightColor' => '000',
			'borderLeftSize' => 6,
			'borderLeftColor' => '000',
			'borderBottomSize' => 6,
			'borderBottomColor' => '000',
			'width' => 500
		];
		$innerTableRightCellStyle = [
			'borderLeftSize' => 6,
			'borderLeftColor' => '000',
			'borderBottomSize' => 6,
			'borderBottomColor' => '000',
			'width' => 500
		];
		$innerTableFontStyle = [
			'size' => 9
		];
		$innerTableParagraphStyle = [
			'align' => 'center'
		];

		$resource = Resource::findOne($id);

		$filename = $resource->name. '.docx';


		
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$xmlWriter->save("php://output");
	}

	public function actionAdditiondata() {
		$request = file_get_contents("php://input");
		$data = json_decode($request);
		for($i = 0; $i < count($data) - 2; $i++){
			$coord = new Coordinates();
			$coord->lat = $data[$i][0];
			$coord->lng = $data[$i][1];
			$coord->registration_number = $data[count($data) - 1][0];
			$coord->class_id = $data[count($data) - 2][0];
			$coord->save();
		}
	}

    public function actionCreatingrequest () {
		$request_data = file_get_contents("php://input");
		$data = json_decode($request_data);
		$user = User::find()->select(['user_id'])->where(['user_data_id' => $data->user_id])->one();
		$request = new Request();
		$request->res_id = $data->registration_number;
		$request->type = $data->requestType;
		$request->sender = $user->user_id;
		$request->reciever = (int) $data->reciever_user_id;
		$request->save();
	}

	public function actionRegistrationnumber (){
		$request= \Yii::$app->request->get();
		$community_id = User::find()->select(['community_id'])->where(['user_data_id' => $request['user_id']])->asArray()->one();
		$prefix = Community::find()->select(['prefix'])->where(['community_id' => $community_id])->asArray()->one();
		$data[] = $prefix['prefix'];
		if(isset($prefix)){
			$reg_num = Resource::find()->select(['registration_number'])->andFilterWhere(['like', 'registration_number', $prefix])->orderBy('registration_number DESC')->asArray()->one();
			$data[] = $reg_num['registration_number'];
		}
		return $data;
	}
	public function actionGettingdata(){
		$request= \Yii::$app->request->get();
		$params = [':min_lat' => $request['min_lat'], ':max_lat' => $request['max_lat'], ':min_lng' => $request['min_lng'], ':max_lng' => $request['max_lng']];
		$sql1 = "SELECT name, coordinates, registration_number FROM resource WHERE registration_number IN (SELECT registration_number FROM coordinates WHERE lat BETWEEN :min_lat AND :max_lat UNION SELECT registration_number FROM coordinates WHERE lng BETWEEN :min_lng AND :max_lng)";
		$sql2 = "SELECT name, coordinates, registration_number FROM resource WHERE registration_number IN (SELECT registration_number FROM coordinates WHERE lat BETWEEN :min_lat AND :max_lat AND class_id = :class_id UNION SELECT registration_number FROM coordinates WHERE lng BETWEEN :min_lng AND :max_lng AND class_id = :class_id)";
		if(strlen($request['name'])){
			$class_id = ResourceClass::find()->select(['class_id'])->where(['name' => $request['name']])->asArray()->one();
			$params[':class_id'] = $class_id['class_id'];
			$data = \Yii::$app->db->createCommand($sql2)->bindValues($params)->queryAll();
		}else{
			$data = \Yii::$app->db->createCommand($sql1)->bindValues($params)->queryAll();
		}
        return $data;
	}

}