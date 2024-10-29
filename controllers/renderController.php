<?php
namespace renderController;
use core\route;
use core\modules\twig;
use Roblox\Grid\Rcc\RCCServiceSoap;
use Roblox\Grid\Rcc\ScriptExecution;
use Roblox\Grid\Rcc\Job;
use core\conf;
enum renderFailureType {
    case notFound;
    case alreadyRendered;
    case unknown;
}

class renderController
{

    function render($id, $type)
    {
        $jobId = uniqid();
        $RCCServiceSoap = new RCCServiceSoap("127.0.0.1", 64989);
        $renderScript = str_replace(["{id}", "{file}"], [$id, "Png"], file_get_contents(__FWDIR__ .'/files/scripts/rendering/' . $type . '.json'));
        $job = new Job($jobId);
        $script = new ScriptExecution("RenderThumbnail", $renderScript);
        [$renderB64, $assetDependencies] = $RCCServiceSoap->BatchJob($job, $script);

        header('x-asset-dependencies', implode(',', $assetDependencies));
        $image = base64_decode($renderB64);
    
        switch ($type) {
            case "R6":
                $folderOut = "FullBody";
                break;
            case "R15":
                $folderOut = "Closeup";
                break;
            case "Shirt":
                $folderOut = "Clothing";
                break;
            default:
                $folderOut = $type;
        }
    
        $outputName = sprintf("ID%s.png", $id);
        if (!file_exists(__FWDIR__ .'/files/renders/' . $folderOut)) {
            mkdir(__FWDIR__ .'/files/renders/' . $folderOut, 0777, true); 
        }
        file_put_contents(__FWDIR__ .'/files/renders/' . $folderOut . '/' . $outputName, $image);
        $image = file_get_contents(__FWDIR__ .'/files/renders/' . $folderOut . '/' . $outputName);
    }
}
