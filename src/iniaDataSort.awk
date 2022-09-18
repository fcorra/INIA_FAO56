#!/usr/bin/awk -f

# Script para extraer la información de los archivos de la Red INIA de
# Agrometeorología y generar el script de PHP que determinará la ETO
# utilizando fao56Alg.php

# Los datos de INIA vienen separados por "," (no comment), y además se
# trata todo como texto. Incluso los valores numéricos.
# Es posible usar FPAT, pero después pensé en simplemente usar FS con ","
# recordando eliminar la \" inicial del campo $1 y final de $NF.
# Esto solo es válido porque (imagino) no habrá "," anidadas dentro de los
# campos de texto.

# Además los datos vienen en dos archivos, no uno, porque no es posible
# descargar más de 5 variables cada vez. En todo caso, ET0 siempre se descarga
# aparte. Por lo tanto este script tiene que procesar dos archivos. En el
# primero (única vez que FNR == NR) rescata las variables de clima y en el
# segundo ET0. Este script se ejecuta, por lo tanto:

# iniaDataSort.awk ARCHIVO_CLIMATICO.csv ARCHIVO_ET0.csv

# El ARCHIVO_CLIMATICO.csv vendrá con las siguientes columnas:
#	* "Tiempo UTC-4"
#	* "Temperatura del Aire Mínima ºC"
#	* "Temperatura del Aire Máxima ºC"
#	* "Humedad Relativa %"
#	* "Radiación Solar Mj/m²"
#	* "Velocidad de Viento km/h"
#	* "Temperatura del Aire Mínima % de datos"
#	* "Temperatura del Aire Máxima % de datos"
#	* "Humedad Relativa % de datos"
#	* "Radiación Solar % de datos"
#	* "Velocidad de Viento % de datos"

# El ARCHIVO_ET0.csv vendrá con las siguientes columnas:
#	* "Tiempo UTC-4"
#	* "La Platina"
#	* "La Platina % de datos"

# El otro punto importante, es que los datos de INIA comienzan en el 6. Bien.
# Sin embargo terminan 4 filas antes del final. Como no sé (probablemente no
# es posible) indicarle a AWK que se detenga 4 lineas antes del final entonces
# uso las variables *str* y *end*. *str* no representa ningún inconveniente
# para el uso futuro, sin embargo *end* sí. ¿Por qué? porque no siempre se
# trabajará con la misma cantidad de datos. ¿Podríamos hacer un buffer y restar
# los cuatro últimos $0? ¿Algo como end = NFR - 4? Sí, pero no lo hice.

# La Latitud de estación La Platina es -33.56 y la Elevación 630 (hard coded).

BEGIN {
	# FPAT="([^,]+)|(\"[^\"]+\")"
        FS="\",\""
	str = 6
	end = 373
}
{
	if (FNR > str && FNR < end) {
		if (NR == FNR) {
			fecha[FNR] = $1
                        gsub (/"/, "", fecha[FNR])
			temp_min[FNR] = $2
			temp_max[FNR] = $3
			hr_med[FNR] = $4
			rad_sol[FNR] = $5
			wind[FNR] = $6
			j++
		} else {
			eto[FNR] = $2
		}	
	}
}
END {
	print ("<?php")
	print ("include \"fao56Alg.php\";")

	print ("$fecha = array(")
	for (i = str; i < end; i++)
		printf "\"%s\",",fecha[i]
	print (");\n$temp_min = array(")
	for (i = str; i < end; i++)
		printf "%f,",temp_min[i]
	print (");\n$temp_max = array(")
	for (i = str; i < end; i++)
		printf "%f,",temp_max[i]
	print (");\n$hr_med = array(")
	for (i = str; i < end; i++)
		printf "%f,",hr_med[i]
	print (");\n$rad_sol = array(")
	for (i = str; i < end; i++)
		printf "%f,",rad_sol[i]
	print (");\n$vel_viento = array(")
	for (i = str; i < end; i++)
		printf "%f,",wind[i]
	print (");\n$obs = array(")
	for (i = str; i < end; i++)
		printf "%f,",eto[i]
	print (");\n")

	print ("$my_data = new METEO_DATA (-33.56, 630, 0.23, $fecha, $rad_sol, $temp_min, $temp_max, $hr_med, $vel_viento);")
	print ("$pred = $my_data->print();")
        print ("$n = count ($obs);")
	print ("echo \"Obs\tPred\".\"\\n\";")
        print ("for ($i = 0; $i < $n; $i++)")
        print ("echo $obs[$i] . \"\\t\" . $pred[$i] . \"\\n\";")
	print ("?>")
}
