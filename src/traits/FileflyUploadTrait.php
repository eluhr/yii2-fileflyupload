<?php

namespace eluhr\fileflyupload\traits;

use creocoder\flysystem\Filesystem;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\MountManager;
use Yii;

/**
 * --- MAGIC GETTERS ---
 *
 * @property string $localFs
 * @property string $storageFs
 *
 * @author Elias Luhr <e.luhr@herzogkommunikation.de>
 */
trait FileflyUploadTrait
{
    /**
     * Name of the local fs component
     *
     * Must be a instance of creocoder\flysystem\LocalFilesystem
     *
     * @return string
     */
    abstract public function getLocalFs(): string;

    /**
     * Name of the storage fs component
     *
     * @return string
     */
    abstract public function getStorageFs(): string;

    /**
     * Setup mount manager
     *
     * @return \League\Flysystem\MountManager
     * @throws \yii\base\InvalidConfigException
     */
    protected function mountManager(): MountManager
    {
        /** @var \creocoder\flysystem\LocalFilesystem $localFsComponent */
        $localFsComponent = Yii::$app->get($this->getLocalFs());
        /** @var \creocoder\flysystem\Filesystem $storageFsComponent */
        $storageFsComponent = Yii::$app->get($this->getStorageFs());

        $manager = new MountManager();
        $manager->mountFilesystems([
            'local' => $localFsComponent->filesystem,
            'storage' => $storageFsComponent->filesystem,
        ]);
        return $manager;
    }

    /**
     * Refresh filefly hashmap
     *
     * @param string $relativePath
     *
     * @return bool
     */
    public function refreshFileFlyApiHashmap(string $relativePath): bool
    {
        try {
            /** @var Filesystem $storageFs */
            $storageFs = Yii::$app->get($this->getStorageFs());
            $storageFs->listContents(dirname($relativePath));
            return true;
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
        }
        return false;
    }

    /**
     * Move file from local fs to another fs
     *
     * @param string $relativePath
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function moveLocalFileToStorage(string $relativePath): bool
    {
        $manager = $this->mountManager();
        try {
            if ($manager->has('storage://' . $relativePath)) {
                $manager->delete('storage://' . $relativePath);
            }
            if ($manager->move('local://' . $relativePath, 'storage://' . $relativePath)) {
                return $this->refreshFileFlyApiHashmap(dirname($relativePath));
            }
        } catch (FileNotFoundException $e) {
            Yii::error($e->getMessage(), __METHOD__);
        }
        return false;
    }
    
    /**
     * Remove file from storage if exists
     *
     * @param string $relativePath
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function deleteFromStorage(string $relativePath): bool
    {
        $manager = $this->mountManager();
        try {
            if ($manager->has('storage://' . $relativePath)) {
                return $manager->delete('storage://' . $relativePath);
            }
        } catch (FileNotFoundException $e) {
            Yii::error($e->getMessage(), __METHOD__);
        }
        return true;
    }
}
