<?php

class METEO_DATA
{
	public float $Latitud;
	public float $Altitud;
	public $data = array(
			"Fecha" => array (),
			"Radiación solar" => array (),
			"Temperatura mínima" => array (),
			"Temperatura máxima" => array (),
			"Humedad relativa media" => array (),
			"Velocidad del viento" => array (),
			"Radiación extraterrestre" => array (),
			"Radiación solar día despejado" => array (),
			"Radiación neta solar" => array (),
			"Presión de vapor a saturación" => array (),
			"Presión real de vapor" => array (),
			"Radiación neta de onda larga" => array (),
			"Radiación neta" => array (),
			"Déficit de presión de vapor" => array (),
			"Pendiente presión de vapor vs temperatura" => array (),
			"Flujo de calor del suelo" => array (),
			"Evapotranspiración de referencia" => array (),
	);

	public function __construct (
		$latitud, 
		$altitud,
		$albedo,	
		$fecha, 
		$radiacion_solar, 
		$temp_min, 
		$temp_max, 
		$humedad_relativa, 
		$velocidad_del_viento) 
	{
		$this->Latitud = (float) $latitud;
		$this->Altitud = (float) $altitud;
		$this->Albedo = (float) $albedo;
		$this->Psicrometrica = $this->psicrometrica ($this->Altitud);

		$max_day = count ($fecha);

		for ($i = 0; $i < $max_day; $i++) {

			array_push ($this->data["Fecha"], $fecha[$i]);
			array_push ($this->data["Radiación solar"], $radiacion_solar[$i]);
			array_push ($this->data["Temperatura mínima"], $temp_min[$i]);
			array_push ($this->data["Temperatura máxima"], $temp_max[$i]);
			array_push ($this->data["Humedad relativa media"], $humedad_relativa[$i]);
			array_push ($this->data["Velocidad del viento"], $velocidad_del_viento[$i]);
			array_push ($this->data["Radiación extraterrestre"],
				$this->r_a ($this->data["Fecha"][$i], $this->Latitud));
			array_push ($this->data["Radiación solar día despejado"],
				$this->r_so ($this->Altitud, $this->data["Radiación extraterrestre"][$i]));
			array_push ($this->data["Radiación neta solar"],
				$this->r_ns ($this->Albedo, $this->data["Radiación solar"][$i]));
			array_push ($this->data["Presión de vapor a saturación"],
				$this->e_sat_med (
					$this->data["Temperatura máxima"][$i],
					$this->data["Temperatura mínima"][$i]
				)
			);
			array_push ($this->data["Presión real de vapor"],
				$this->e_a (
					$this->data["Humedad relativa media"][$i],
					$this->data["Temperatura máxima"][$i],
					$this->data["Temperatura mínima"][$i]
				)
			);
			array_push ($this->data["Radiación neta de onda larga"],
			       	$this->r_nl (
					$this->data["Temperatura máxima"][$i],
					$this->data["Temperatura mínima"][$i],
					$this->data["Presión real de vapor"][$i],
					$this->data["Radiación solar"][$i],
					$this->data["Radiación solar día despejado"][$i]
				)
			);
			array_push ($this->data["Radiación neta"],
			       	$this->rn (
					$this->data["Radiación neta solar"][$i],
					$this->data["Radiación neta de onda larga"][$i]
				)
			);
			array_push ($this->data["Déficit de presión de vapor"],
			       	$this->dpv (
					$this->data["Presión de vapor a saturación"][$i],
					$this->data["Presión real de vapor"][$i]
				)
			);
			array_push ($this->data["Pendiente presión de vapor vs temperatura"],
			       	$this->delta (
					$this->data["Temperatura máxima"][$i],
					$this->data["Temperatura mínima"][$i]
				)
			);
			array_push ($this->data["Flujo de calor del suelo"],
			       	$this-> g());
			array_push ($this->data["Evapotranspiración de referencia"],
			       	$this->et0 (
					$this->data["Pendiente presión de vapor vs temperatura"][$i],
					$this->data["Radiación neta"][$i],
					$this->data["Flujo de calor del suelo"][$i],
					$this->Psicrometrica,
					$this->data["Temperatura máxima"][$i],
					$this->data["Temperatura mínima"][$i],
					$this->data["Velocidad del viento"][$i],
					$this->data["Déficit de presión de vapor"][$i]
				)
			);
		}
	}

	public function print () : array
	{
		//var_dump($this->data);
    return $this->data["Evapotranspiración de referencia"];
	}

	private function r_a(string $fecha, float $lat) : float
	{
		
		/* La función fecha devuelve un timestamp en distintos formatos,
		 * la opción "z" es el día juliano [0-365]. */
		$dia_juliano = date ("z", strtotime($fecha));

		$distancia_tierra_sol = 1 + 0.033 * cos (((2 * pi()) / 365) * $dia_juliano);
		$declinacion_solar = 0.409 * sin (((2 * pi()) / 365) * $dia_juliano - 1.39);
		$latitud_radianes = ($lat * pi()) / 180;
		$angulo_puesta_de_sol = acos (-tan ($latitud_radianes) * tan ($declinacion_solar));

		$uno = ((24 * 60) / pi()) * 0.082 * $distancia_tierra_sol;
		$dos = $angulo_puesta_de_sol * sin($latitud_radianes) * sin($declinacion_solar);
		$tre = cos($latitud_radianes) * cos($declinacion_solar) * sin($angulo_puesta_de_sol);
		$r_a = $uno * ($dos + $tre);
		return $r_a;
	}

	private function r_so(float $z, float $r_a) : float
	{
		$r_so = (0.75 + 2 * pow(10,-5) * $z) * $r_a;
		return $r_so;
	}

	private function e_sat ($temp) : float 
	{
		$e_sat = 0.6108 * exp((17.27 * $temp) / ($temp + 237.3));
		return $e_sat;
	}

	private function e_a (float $hr_media, float $temp_max, float $temp_min) : float
	{
		$temp_media = $this->temp_med ($temp_max, $temp_min);
		$e_a = $hr_media / 100 * $this->e_sat ($temp_media);
		return $e_a;
	}

	private function r_nl(float $t_max, float $t_min, float $e_a, float $r_s, float $r_so) : float
	{
		$uno = 4.903 * pow(10,-9) * ((pow(($t_max+273.16),4) + pow(($t_min+273.16),4)) * 0.5);
		$dos = (0.34 - 0.14 * sqrt($e_a));
		$tre = (1.35 * $r_s / $r_so - 0.35);
		$r_nl = $uno * $dos * $tre;
		return $r_nl;
	}

	private function r_ns (float $alpha, float $r_s) : float
	{
		$r_ns = (1 - $alpha) * $r_s;
		return $r_ns;
	}

	private function rn(float $rns, float $rnl) : float
	{
		$rn = $rns - $rnl;
		return $rn;
	}

	private function e_sat_med ($temp_max, $temp_min) : float 
	{
		$e_sat_med = ($this->e_sat($temp_max) + $this->e_sat($temp_min)) * 0.5;
		return $e_sat_med;
	}

	private function dpv($e_sat, $e_a) : float {
		return $e_sat - $e_a;
	}

	private function g() : float {
		return 0.0;
	}

	private function temp_med(float $temp_max, float $temp_min) : float
	{
		return ($temp_max + $temp_min) * 0.5;
	}

	private function psicrometrica(float $z) : float
	{
		$presion_atm = 101.3 * pow(((293 - 0.0065 * $z) / 293),5.26);
		$psi = (1.013 * pow(10, -3) * $presion_atm * pow(0.622, -1) * pow(2.45, -1));
    return $psi;
    //return 0.665 * pow(10, -3);
	}

	private function delta(float $temp_max, float $temp_min) : float
	{
		$temp_media = $this->temp_med ($temp_max, $temp_min);
		return 4098 * $this->e_sat ($temp_media) * pow(($temp_media + 237.3), -2);
	}

	private function et0(
		float $delta,
		float $rn,
		float $g,
		float $psi,
		float $temp_max,
		float $temp_min,
		float $v_viento,
		float $dpv
	) : float 
	{
		$temp_media = $this->temp_med ($temp_max, $temp_min);
		$num = 0.408 * $delta * ($rn - $g) + $psi * (900 / ($temp_media + 273)) * $v_viento * $dpv;
		$den = $delta + $psi * (1 + 0.34 * $v_viento);
		return $num / $den;
 	}

}
?>
