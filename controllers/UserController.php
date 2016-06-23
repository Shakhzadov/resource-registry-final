<?php
namespace app\controllers;

use yii\base\Exception;
use app\models\User;
use app\models\LoginForm;
use app\models\Role;
use app\models\PersonalData;
use app\models\Community;
use yii\web\Session;
use app\controllers\AppController;

class UserController extends AppController 
{
    public $modelClass = 'app\models\User';
    public function actionLogin() {
        $modelLoginForm = new LoginForm();
        $post = \Yii::$app->request->post();
        
        if ($modelLoginForm->load($post, '') && $modelLoginForm->login() ) {
            $roleName = Role::findOne(\Yii::$app->user->identity->role_id);
            return [
                'username' => \Yii::$app->user->identity->username,
                'role' => $roleName->name,
                'isLogined' => true,
                'userDataID' => \Yii::$app->user->identity->user_data_id,
                'communityId' => \Yii::$app->user->identity->community_id
            ];  
        } else {
            return $modelLoginForm;
        }
    }

    public function actionLogout(){
        \Yii::$app->user->logout();
        return 'Вихід здійснено';
    }
    public function actionAdduser()
    {
        if (!$post = \Yii::$app->getRequest()->getBodyParams()) {
            throw new \yii\web\HttpException(400, 'Дані не отримані');
        }
        if (User::findByUsername($post['username'])) {
            throw new \yii\web\HttpException(400, 'Користувач з таким логіном уже існує');
        }
        $userModel = new User();

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $personalDataModel = new PersonalData();
            $personalDataModel->last_name = $post['last_name'];
            $personalDataModel->first_name = $post['first_name'];
            $personalDataModel->middle_name = $post['middle_name'];
            $personalDataModel->passport_series = $post['passport_series'];
            $personalDataModel->passport_number = $post['passport_number'];
            $personalDataModel->address = $post['address'];
            if (!$personalDataModel->save()) {
                foreach($personalDataModel->errors as $key) {
                    $errorMessage .= $key[0];
                }
                throw new \yii\web\HttpException(422,$errorMessage);
            }
            $userModel = new User();
            $userModel->username = $post['username'];
            $password = $post['password'];
            $validator = new \yii\validators\StringValidator([
                'min' => 3,
                'max' => 12,
                'tooShort' => 'Пароль повинен містити мінімум {min, number} символи',
                'tooLong' => 'Пароль повинен містити не більше {max, number} символів'
            ]);
            if (!$validator->validate($password, $error)) {
                throw new \yii\web\HttpException(422, $error);
            }
            $userModel->setPassword($post['password']);
            $userModel->email = $post['email'];
            $userModel->role_id = $post['role_id'];
            $userModel->community_id = $post['community_id'];
            $userModel->activation_status = $post['activation_status'];
            $userModel->user_data_id = $personalDataModel->personal_data_id;
            $userModel->generateAuthKey();
            if (!$userModel->save()) {
                foreach($userModel->errors as $key) {
                    $errorMessage .= $key[0];
                }
                throw new \yii\web\HttpException(422,$errorMessage);
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new \yii\web\HttpException(422,$errorMessage . $error);
            return $errorMessage . $error;
        }
        exit('end');
    }
    public function actionRestorepass() {
        if (!$post = \Yii::$app->getRequest()->getBodyParams()) {
            throw new \yii\web\HttpException(400, 'Дані не отримані');
        }
        $model = User::findByUsername($post['username']);
        if (!$model->username) {
            throw new \yii\web\HttpException(400, 'Даного користувача не існує');
        }

        $model->generatePasswordResetToken();
        $recover_link = 'http://rr.com/site/restorepassword?u=' . $model->username . '&p=' . $model->password_reset_token;
        $acc = $post['username'];
        \Yii::$app->mailer->compose()
            ->setFrom('resourceregistry@gmail.com')
            ->setTo($model->email)
            ->setSubject('Відновлення паролю на сайті rr.com')
            ->setTextBody('')
            ->setHtmlBody("Ви зробили запит на відновлення пароля для акаунту: \"$acc\" Перейдіть за посиланням, щоб скинути старий пароль, та встановити новий:<b> <a href=\"$recover_link\">Ссилка для відновлення паролю</a> </b>")
            ->send();
        $model->save();
        return true;
    }
    public function actionGetuser() {
        // Get user from DB
        if (!$post = \Yii::$app->getRequest()->getBodyParams()) {
            throw new \yii\web\HttpException(400, 'Дані не отримані');
        }
        $model = User::getUserByUserName($post['username']);
        if (!$model->username) {
            throw new \yii\web\HttpException(400, 'Даного користувача не існує');
        }
        $model-> save();
        return $model;
    }
    public function actionChangepass() {
        /*echo \Yii::$app->session->get('role');*/
        if (!$post = \Yii::$app->getRequest()->getBodyParams()) {
            throw new \yii\web\HttpException(400, 'Дані не отримані');
        }
        $model = User::findByPasswordResetToken($post['token']);
        if (!$model) {
            throw new \yii\web\HttpException(422, 'Ключ для відновлення паролю не є коректним');
        }
        $password = $post['password'];
        $validator = new \yii\validators\StringValidator([
            'min' => 3,
            'max' => 12,
            'tooShort' => 'Пароль повинен містити мінімум {min, number} символи',
            'tooLong' => 'Пароль повинен містити не більше {max, number} символів'
        ]);
        if (!$validator->validate($password, $error)) {
            throw new \yii\web\HttpException(422, $error);
        }
        $model->setPassword($password);
        $model->removePasswordResetToken();
        $model->save();
        echo $model->username;
        exit('ok');
    }

    public function actionChangepassloged() {
        if (!$post = \Yii::$app->getRequest()->getBodyParams()) {
            throw new \yii\web\HttpException(400, 'Дані не отримані');
        }
       $model = User::getUserByUserName($post['username']);
        if (!$model->username) {
            throw new \yii\web\HttpException(400, 'Даного користувача не існує');
        }
        $model->setPassword($post['password']);
        $model->removePasswordResetToken();
        $model->save();
        echo $model->username;
        exit('changed');
    }
    public function actionChangeemail() {
        if (!$post = \Yii::$app->getRequest()->getBodyParams()) {
            throw new \yii\web\HttpException(400, 'Дані не отримані');
        }
       $model = User::getUserByUserName($post['username']);
        if (!$model->username) {
            throw new \yii\web\HttpException(400, 'Даного користувача не існує');
        }
        $request = User::find()->select('*')->where(['username' => $post['username']])->one();
        $request->email = $post['email'];
        $request->update();
        exit('changed');
    }
    
    public function actionUserdata() {
        $request = \Yii::$app->request->get();
        $sort = 'last_name ASC';  
        if($request['sort']=="desc") {
            $sort = 'last_name DESC';
        }
      
        $words = explode(' ', $request['value']);
        if(sizeof($words) != 2) {
            $filters = [
                'or',
                ['like', 'first_name', $words[0]],
                ['like', 'last_name', $words[0]],
                ['like', 'role.name', $words[0]]
            ];
        } else {
            $filters = ['or', [
                'and',
                ['like', 'first_name', $words[0]],
                ['like', 'last_name', $words[1]]
            ], [
                'and',
                ['like', 'first_name', $words[1]],
                ['like', 'last_name', $words[0]]
            ]];
        }

        if($request['userId']) {
            $getdata = User::find()
            ->select(['user_id','username','last_name','first_name','email','middle_name','passport_series','passport_number','address','role.name as role_name', 'user.role_id as role_id', 'community.name as community_name', 'prefix','user.community_id as communityId','activation_status'])
            ->joinWith('personalData')->joinWith('userRole')->joinWith('community')           
            ->andFilterWhere(['user_id' => $request['userId']])
            ->asArray();
        } else {
            if(!$request['currentCommId']) {
                $getdata = User::find()
                ->select(['user_id','username','last_name','first_name','email','middle_name','passport_series','passport_number','address','role.name as role_name', 'user.role_id as role_id', 'community.name as community_name','user.community_id as communityId','activation_status'])
                ->joinWith('personalData')->joinWith('userRole')->joinWith('community')
                ->andFilterWhere($filters)
                ->andFilterWhere(['like', 'activation_status', $request['activation_status']])
                ->orderBy($sort)
                ->asArray();
            } else {
                $getdata = User::find()
                ->select(['user_id','username','last_name','first_name','passport_series','passport_number','user.community_id as communityId','role.name as role_name','activation_status'])
                ->joinWith('personalData')->joinWith('userRole')
                ->andFilterWhere($filters)
                ->andFilterWhere(['like', 'activation_status', $request['activation_status']])
                ->andFilterWhere(['user.community_id' => $request['currentCommId']])
                ->orderBy($sort)
                ->asArray(); 
            }
        }
        return self::buildPagination($getdata, 10);
    }
    
    public function actionEdituserdata() {    
        $request = \Yii::$app->request->getBodyParams();

        $user = User::findOne(['user_id' => $request['userId']]);
        $personalData = $user->personalData;
        
        // edit user table columns
        $user->role_id = $request['role_id'];
        $user->username=$request['username'];
        $user->email=$request['email'];
        $user->community_id=$request['community_id'];

        // edit personal_data table columns
        $personalData->last_name=$request['last_name'];
        $personalData->first_name=$request['first_name'];
        $personalData->middle_name=$request['middle_name'];
        $personalData->passport_series=$request['passport_series'];
        $personalData->passport_number=$request['passport_number'];
        $personalData->address=$request['address'];

        $user->update();
        $personalData->update();
    }

    public function actionChangeactivationstatus() {
        $request= \Yii::$app->request->get();
        $user = User::findOne(['user_id' => $request['user_id']]);
        $user->activation_status=$request['activation_status'];
        $user->role_id=$request['role_id'];
        $user->community_id=$request['community_id'];
        if($user->role_id==3){
            $getAdmin = User::find()->select(['activation_status', 'user_id'])->where(['role_id' => $request['role_id'], 'activation_status' => 1])->asArray()->all();
            if(sizeof($getAdmin) == 1 && ($user->activation_status==0)){
                foreach($user->errors as $key){
                    $errorMessage .= $key[0];
                }
                throw new \yii\web\HttpException(422,$errorMessage);
            } else 
                $user->update();

        } else{
            $getAdmin = User::find()->select(['activation_status', 'user_id', 'community_id'])->where(['role_id' => $request['role_id'],'community_id' => $request['community_id'], 'activation_status' => 1])->asArray()->all();
            if(sizeof($getAdmin) == 1 && ($user->activation_status==0)){
                foreach($user->errors as $key){
                    $errorMessage .= $key[0];
                }
                throw new \yii\web\HttpException(422,$errorMessage);
            } else 
                $user->update();
        }
    }
}
