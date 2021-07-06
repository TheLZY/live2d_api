<?php
isset($_GET['id']) ? $id = $_GET['id'] : exit('error');

require '../tools/modelList.php';
require '../tools/modelTextures.php';
require '../tools/jsonCompatible.php';

$id = explode('-', $id);
$modelId = (int)$id[0];
$modelTexturesId = isset($id[1]) ? (int)$id[1] : 0;

$_index  = new GetModelJson();
$json = $_index->getJson($modelId,$modelTexturesId);

class GetModelJson{
    public function getJson($modelId,$modelTexturesId){
        
        $modelList = new modelList();
        $modelName = $modelList->id_to_name($modelId);
        if (is_array($modelName)) {
            if( $modelTexturesId > 0){
                $modelName = $modelName[$modelTexturesId-1];
            }else{
                $modelName = $modelName[0];
            }
            $result = $this->getFileJson($modelName);
            if($result["ver"] == 1){
                $json = $result["json"];
            }else{
                
            }
            $json =  $this->existsVerJson($modelName);
        } else {
            $json = $this->existsVerJson($modelName);
            if ($modelTexturesId > 0) {
                $modelTextures = new modelTextures();
                $modelTexturesName = $modelTextures->get_name($modelName, $modelTexturesId);
                if (isset($modelTexturesName)){
                    if(is_array($modelTexturesName)){
                        $json['textures'] = $modelTexturesName;
                    }else{
                        $json['textures'] = array($modelTexturesName);
                    }
                } 
            }
        }
        
    }

    private function getV2Json($modelName,$json){
        $jsonCompatible = new jsonCompatible();
        foreach ($json['textures'] as $k => $texture)
        $json['textures'][$k] = '../model/' . $modelName . '/' . $texture;

        $json['model'] = '../model/'.$modelName.'/'.$json['model'];
        if (isset($json['pose'])) $json['pose'] = '../model/'.$modelName.'/'.$json['pose'];
        if (isset($json['physics'])) $json['physics'] = '../model/'.$modelName.'/'.$json['physics'];

        if (isset($json['motions']))
            foreach ($json['motions'] as $k => $v) foreach($v as $k2 => $v2) foreach ($v2 as $k3 => $motion)
                if ($k3 == 'file' || $k3 == 'sound') $json['motions'][$k][$k2][$k3] = '../model/' . $modelName . '/' . $motion;

        if (isset($json['expressions']))
            foreach ($json['expressions'] as $k => $v) foreach($v as $k2 => $expression)
                if ($k2 == 'file') $json['expressions'][$k][$k2] = '../model/' . $modelName . '/' . $expression;

        header("Content-type: application/json");
        echo $jsonCompatible->json_encode($json);
    }

    public function getFileJson($modelName){
        $result = array();
        $filePathv2 = '../model/'.$modelName.'/index.json';
        $filePathv3 = '../model/'.$modelName.'/'.$modelName.'.model3.json';
        if(file_exists($filePathv2)){
            $result["ver"] = 1;
            $result["modelPath"] = $filePathv2;
            $result["json"] = json_decode(file_get_contents($filePathv2), 1);
            return $result;
        }else if(file_exists($filePathv3)){
            $result["ver"] = 3;
            $result["modelPath"] = $filePathv3;
            $result["json"] = json_decode(file_get_contents($filePathv3), 1);
            return $result;
        }else{
            throw new Exception("这个文件不是模型引导文件。This file is not '.json' OR 'model3.json'");
        }
    }
} 