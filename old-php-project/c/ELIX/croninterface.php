<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for  the  server
 */
namespace ELIX;

class CronInterface{
    protected $key=null;
    protected $lastrun=0;
    protected $now=0;
    protected $hour=0;
    protected $day=0;
    protected $priority=0;
    public function __construct() {
        if(func_num_args()>0){
            $this->lastrun = (int)func_get_arg(0);
        }
        if(func_num_args()>1){
            $this->key = func_get_arg(1);
        }
        $this->now = time();
        $this->hour = date('G');
        $this->day = date('w');
    }
    public function __call($name, $arguments) {}
    
    public function run(){
        //to be implemented in extended class
        $this->lastrun = $this->now;
    }
    public function currentHour(){
        return $this->hour;
    }
    public function getNow(){
        return $this->now;
    }
    public function getLast(){
        return $this->lastrun;
    }
    public function getKey(){
        return $this->key;
    }
    public function setPriority($value){
        $this->priority = $value;
    }
    public function getPriority(){
        return $this->priority;
    }
    public function since(){
        //seconds since last run
        return $this->now - $this->lastrun;
    }
    public function sinceMinute(){
        return floor($this->since() / 60);
    }
    public function sinceHour(){
        return floor($this->since() / 3600);
    }
    public function sinceDay(){
        return floor($this->since() / 86400);
    }
    public function sinceWeek(){
        return floor($this->since() / 604800);
    }
    public function sinceMonth(){
        //28 days
        return floor($this->since() / 2419200);
    }
    public function sinceYear(){
        //365.25 days
        return floor($this->since() / 31557600);
    }
}