#!/usr/bin/awk -f

# Script para extraer la información de los archivos del CFWSC y generar
# el script de PHP que determinará la ETO utilizando fao56Alg.php

# Los datos del CFWSC vienen en un archivo *.txt* que contiene los datos
# climáticos para todos los pixeles y para todas las fechas del año de 
# acuerdo a la siguiente estructura:
#	*  date: Date data representation
#	*  latitude: Latitude of Pixel value
#	*  longitude: Longitude of Pixel value
#	*  pixel: Pixel ID number
#	*  PET: Potential ET(mm/day)
#	*  RET: Reference ET(mm/day)
#	*  solar: Solar Radiation - Daily Insolation (MegaJoules/sq meter/day)
#	*  RHmax: Maximum Relative Humidity for day (%)
#	*  RHmin: Minimum Relative Humidity for day (%)
#	*  Tmax: Maximum Temperature for day (C)
#	*  Tmin: Minimum Temperature for day (C)
#	*  Wind: Wind Speed (meters/second)

# Los valores inexistentes (no hay, revisé) tienen valores de -9999.900. 

# El archivo acompañante Quality_Codes_2017.txt indica la confiabilidad de los
# valores de radiación para un día específico. La mayor parte son 'confiables'
# (=1) y algunos son 'usables' (=2). En este análisis los usaremos todos,
# aunque sería posible procesar ambos archivos y generar una data solo con los
# días con datos (!= -9999.900) y con valores de radiación de 'buena calidad' (=1).

# Se retienen solo los datos correspondiente al pixel ID 9338 (hard coded).
# La Latitud del pixel es 24.59 y la Elevación 10m (hard coded).

BEGIN {
	FS="\t"
       	j = 0
}
{
	if ($4 == 9338) {
		year[j] = day[j] = month[j] = $1
		gsub (/[0-9]{4}$/, "", year[j])
		gsub (/^[0-9]{4}/, "", month[j])
		gsub (/[0-9]{2}$/, "", month[j])
		gsub (/^[0-9]{6}/, "", day[j])
		temp_min[j] = $11
		temp_max[j] = $10
		hr_med[j] = ($9 + $8) * 0.5
		rad_sol[j] = $7
		wind[j] = $12
		eto[j] = $6
		j++
	}
}
END {
	print ("<?php")
	print ("include \"fao56Alg.php\";")

	print ("$fecha = array(")
	for (i = 0; i < j; i++){
		printf "\"%s-",day[i]
		printf "%s-",month[i]
		printf "%s\",",year[i]
	}
	print (");\n$temp_min = array(")
	for (i = 0; i < j; i++)
		printf "%f,",temp_min[i]
	print (");\n$temp_max = array(")
	for (i = 0; i < j; i++)
		printf "%f,",temp_max[i]
	print (");\n$hr_med = array(")
	for (i = 0; i < j; i++)
		printf "%f,",hr_med[i]
	print (");\n$rad_sol = array(")
	for (i = 0; i < j; i++)
		printf "%f,",rad_sol[i]
	print (");\n$vel_viento = array(")
	for (i = 0; i < j; i++)
		printf "%f,",wind[i]
	print (");\n$obs = array(")
	for (i = 0; i < j; i++)
		printf "%f,",eto[i]
	print (");\n")

	print ("$my_data = new METEO_DATA (24.59, 10, 0.23, $fecha, $rad_sol, $temp_min, $temp_max, $hr_med, $vel_viento);")
	print ("$pred = $my_data->print();")
        print ("$n = count ($obs);")
	print ("echo \"Obs\tPred\".\"\\n\";")
        print ("for ($i = 0; $i < $n; $i++)")
        print ("echo $obs[$i] . \"\\t\" . $pred[$i] . \"\\n\";")
	print ("?>")
}
