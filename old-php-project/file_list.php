<?php
/**
 * @version 20190415.11
 */
chdir(__DIR__);
include_once('pre.inc');
   
$GET = SAP::getInstance('GET');
$is_sequencing = $GET->sequencing;

$UL = HTML::create('ul');
if ($is_sequencing){
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(SEQ_DIR);
} else {
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(FINAL_DIR);
}
$filesList = [];
foreach($dirIter as $F){
    if ($F->isDot()){
        continue;
    }
    $nm = split_name($F->getbasename('.pdf'));
    if ($is_sequencing){
        $sectionkey = "{$nm['cand']}-{$nm['mc']}-{$nm['remainder']}";
    } else {
        $sectionkey = "{$nm['mc']}-{$nm['cand']}-{$nm['remainder']}";
    }
    $filesList[$sectionkey] = $F->getfilename();
}
ksort($filesList);
$lastSectionKey = '';
$lcand = '';
foreach($filesList as $bn => $filename){
    $nm = split_name($filename);
    $new_section = false;
    
    if ($is_sequencing){
        $sectionkey = "{$nm['mc']}-{$nm['cand']}";
    } else {
        $sectionkey = "{$nm['mc']}";
    }
    
    if ($sectionkey != $lastSectionKey){
        $li = $UL->Add();
        $lastSectionKey = $sectionkey;
        $lbn = $nm['mc'];
        if (isset($csec_subjects[$lbn])){
            $li->append($csec_subjects[$lbn]);
        }
        if ($is_sequencing){
            if ($nm['cand'] != $lcand){
                $lcand = $nm['cand'];
                $li->append(' ' . $lcand);
            }
            $fn = "{$nm['cand']}{$nm['mc']}";
            $a = $UL->Add()->create('a')->target('_blank')
            ->download("{$fn}.zip")->href('sequence.php?prefix=' . $fn)
            ->append("download zip of sequence {$nm['cand']}-{$nm['mc']}");
            $a = $UL->Add()->create('a')->target('_blank')
            ->href('sequence.php?delete_prefix=' . $fn)
            ->append(" delete sequence for  {$nm['cand']}-{$nm['mc']}");
            
        } else {
            $fn = "{$nm['mc']}";
            $a = $UL->Add()->create('a')->target('_blank')
            ->download("{$fn}.zip")->href('sequence.php?mc_prefix=' . $fn)
            ->append("download zip of {$nm['mc']}");
            $a = $UL->Add()->create('a')->target('_blank')
            ->href('sequence.php?delete_mc_prefix=' . $fn)
            ->append(" delete items for {$nm['mc']}");
            
        }
    }
    $a = $UL->Add()->create('a')->target('_blank')
        ->download($filename);
    if ($is_sequencing){
        $a->href('s/' . $filename);
    } else {
        $a->href('f/' . $filename);
    }
        
    
    $a->Append($nm['cand']);
    $a->Append('-');
    $a->create('strong')->Append($nm['mc']);
    //$a->Append('-');
    $a->create('em')->Append($nm['remainder']);
    
}

if ($GET->included){
    return $UL;
}
echo $UL;
