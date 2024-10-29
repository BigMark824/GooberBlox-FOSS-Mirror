<?php
namespace soapController;

use core\route;
use SoapClient;
use SoapFault;

class soapController 
{
    private string $ip;
    private int $port;
    protected SoapClient $client;

    public function __construct(string $ip = "experience-website.gl.at.ply.gg", int $port = 13853) {
        $this->ip = $ip;
        $this->port = $port;
        $this->client = new SoapClient("http://localhost/RCCService.wsdl", [
            'location' => "http://$this->ip:$this->port",
            'uri' => "http://goober.biz/",
            'trace' => true,
            'soap_version' => SOAP_1_1,
            'exceptions' => true // disable later 
        ]);
    }

    public function soapCall(string $name, ?array $args = null): array
	{
		$result = $this->client->__soapCall($name, $args ?? []);
		return static::deserializeArray($result);
	}

    public function callJob(string $script, ?string $jobId)
    {
        if(!$jobId) $jobId = $this->newJobId();
        return $this->soapCall('OpenJobEx', [
            'job' => $jobId,
            'script' => $script,
        ]);
    }

    public static function serializeArray(array $array): array
	{
		array_walk($array, fn(&$v) => $v = static::serializeValue($v));
		return $array;
	}
	
	public static function serializeValue(mixed $value): array
	{
		$luaType = match(gettype($value)) {
			'string' => 'LUA_TSTRING',
			'boolean' => 'LUA_TBOOLEAN',
			'double' => 'LUA_TNUMBER',
			'integer' => 'LUA_TNUMBER',
			'array' => 'LUA_TTABLE',
			'object' => 'LUA_TTABLE',
			'NULL' => 'LUA_TNIL'
		};
		
		$result = array_merge(
			['type' => $luaType],
			match($luaType) {
				'LUA_TTABLE' => ['table' => ['LuaValue' => static::serializeArray((array)array_values((array)$value))]],
				'LUA_TBOOLEAN' => ['value' => json_encode($value)],
				'LUA_TNIL' => [],
				default => ['value' => strval($value)]
			}
		);
		
		return $result;
	}
	
	public static function deserializeArray(object|array $array): array
	{
		$result = reset($array);
		
		if(gettype($result) != 'object' && gettype($result) != 'string')
			return [$result];
		
		if(!property_exists($result, 'LuaValue'))
			return [];
		
		$array = $result->LuaValue;
		if(gettype($array) == 'object')
			$array = [$array];
		
		array_walk($array, fn(&$v) => $v = static::deserializeValue($v));
		return $array;
	}
	
	public static function deserializeValue(object $value): mixed
	{
		switch($value->type)
		{
			case 'LUA_TBOOLEAN':
				return boolval($value->value);
			case 'LUA_TNUMBER':
				return doubleval($value->value);
			case 'LUA_TTABLE':
				if(count((array)$value->table) == 0)
					return [];
				return static::deserializeArray([$value->table]);
			case 'LUA_TNIL':
				return null;
			default:
				return $value->value;
			
		}
	}
    
    private function uuidv4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function newJobId(): string {
        return $this->uuidv4();
    }
}
