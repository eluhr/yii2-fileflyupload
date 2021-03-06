# Yii2 Filefly Upload

A little helper trait to easily upload files from a local filesystem to another filesystem

## Installation

```bash
composer require eluhr/yii2-fileflyupload
```

## Usage

Your config must configure the needed filesystems see [creocoder/yii2-flysystem](https://github.com/creocoder/yii2-flysystem#configuring)

 Use the trait in your Model
 ```php
  namespace example\namespace\models;

  use eluhr\fileflyupload\traits\FileflyUploadTrait;
  use yii\base\Model;
  use yii\helpers\FileHelper;

  class MyModel extends Model
  {
      use FileflyUploadTrait;

      public $file;
      
      public function getLocalFs(): string {
          return 'fsLocal';
      }
      
      public function getStorageFs(): string {
          return 'fsStorage';
      }

      public function rules(): array
      {
          $rules = parent::rules();
          $rules[] = [
              'file',
              'file',
              'skipOnEmpty' => false,
              'extensions' => 'pdf',
              'maxSize' => 3145728 // 3 MB
          ];
          return $rules;
      }

      public function upload(): bool
      {
          if ($this->validate()) {
              $relativePath = '/path/to/file.pdf';
              $absolutePath = \Yii::$app->get($this->getLocalFs())->path . $relativePath;
              if (!FileHelper::createDirectory(dirname($absolutePath))) {
                  return false;
              }
              if ($this->file->saveAs($absolutePath) && $this->moveLocalFileToStorage($relativePath)) {
                  return true;
              }
              $this->addError('file', \Yii::t('model','Error while uploading file'));
          }
          return false;
      }
      
            
      public function beforeDelete()
      {
          if (!$this->deleteFromStorage('/path/to/file.pdf')) {
              return false;
          }
          return parent::beforeDelete();
      }
  }
 ```

 In your controller you just call the method moveLocalFileToStorage
 ```php
  namespace example\namespace\controllers;

  use my\namespace\models\MyModel;
  use yii\web\Controller;
  use yii\web\UploadedFile;
  use Yii;

  class MyController extends Controller
  {
      public function actionUpload()
      {
          $model = new MyModel();
          if (Yii::$app->getRequest()->getIsPost()) {
              $model->file = UploadedFile::getInstance($model, 'file');
              if ($model->upload()) {
                  return $this->redirect(['upload']);
              }
          }
          return $this->render('upload', ['model' => $model]);
      }
  }
 ```

 Your view file can look something like this
  ```php
  use yii\helpers\Html;
  use yii\widgets\ActiveForm;

  $form = ActiveForm::begin();
  echo $form->field($model, 'file')->fileInput(['accept' => 'application/pdf']);
  echo Html::submitButton();
  ActiveForm::end();
  ```

 Note: This is a very basic example. This may not suit your needs. Please do not blindly copy and paste it
 
