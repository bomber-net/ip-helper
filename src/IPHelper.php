<?php

namespace BomberNet\IPHelper;

use function count;
use function strlen;

class IPHelper
	{
		public static function ipVer (string $address):?int
			{
				return match (true)
					{
						!$net=self::net ($address)=>null,
						(bool)self::ip4bin ($net[0])=>4,
						(bool)self::ip6bin ($net[0])=>6,
						default=>null,
					};
			}
		
		public static function extractRange (string $address):?array
			{
				return match (true)
					{
						!$net=self::net ($address)=>null,
						(bool)$base=self::ip4bin ($net[0])=>self::extractIP4range ($base,$net[1]??32),
						(bool)$base=self::ip6bin ($net[0])=>self::extractIP6range ($base,$net[1]??32),
						default=>null,
					};
			}
		
		public static function inRange (string $address,string $IP):?bool
			{
				$range=self::extractRange ($address);
				$ip=self::ipbin ($IP);
				if (!($range && $ip)) return null;
				return strcmp ($ip,$range[0])>=0 && strcmp ($ip,$range[1])<=0;
			}
		
		public static function ipbin (string $address):?string
			{
				$addr=self::ip4bin ($address);
				if (!$addr) $addr=self::ip6bin ($address);
				return $addr;
			}
		
		private static function ip4bin (string $address):?string
			{
				$addr=ip2long ($address);
				return $addr!==false?str_pad (decbin ($addr),32,'0',STR_PAD_LEFT):null;
			}
		
		private static function ip6bin (string $address):?string
			{
				if (count (explode (':::',$address))>1) return null;
				/** @var array $addr */
				$addr=explode ('::',$address);
				switch (count ($addr))
					{
						case 1:
							$addr=explode (':',$addr[0]);
							break;
						case 2:
							$addr[0]=array_filter (explode (':',$addr[0]),static fn ($group) => strlen ($group));
							$addr[1]=array_filter (explode (':',$addr[1]),static fn ($group) => strlen ($group));
							$addr=array_merge ($addr[0],array_fill (0,8-(count ($addr[0])+count ($addr[1])),'0'),$addr[1]);
							break;
						default:
							return null;
					}
				if (count ($addr)!==8) return null;
				$ip='';
				foreach ($addr as $group)
					{
						if (!$group=hexbin ($group)) return null;
						if (strlen ($group)>16) return null;
						$ip.=str_pad ($group,16,'0',STR_PAD_LEFT);
					}
				return $ip;
			}
		
		private static function extractIP4range (string $base,int $mask):?array
			{
				if ($mask>32) return null;
				if ($mask===32) return [$base,$base];
				$length=32-$mask;
				$start=substr_replace ($base,str_repeat ('0',$length),$mask);
				$end=substr_replace ($base,str_repeat ('1',$length),$mask);
				return [$start,$end];
			}
		
		private static function extractIP6range (string $base,int $mask):?array
			{
				if ($mask>128) return null;
				if ($mask===128) return [$base,$base];
				$length=128-$mask;
				$start=substr_replace ($base,str_repeat ('0',$length),$mask);
				$end=substr_replace ($base,str_repeat ('1',$length),$mask);
				return [$start,$end];
			}
		
		private static function net (string $address):?array
			{
				/** @var array $net */
				$net=explode ('/',$address);
				if (count ($net)>1 && (count ($net)>2 || !is_intnum ($net[1]) || $net[1]<0)) return null;
				return $net;
			}
	}
