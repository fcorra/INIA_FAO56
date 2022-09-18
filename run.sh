#!/usr/bin/bash

col_echo() {
	RED="\033[0;31m"
	GRN="\033[0;32m"
	NCO="\033[0m"
	printf "${!1}${2}${NCO}\n"
}

echo "A continuación se comparará el output del algoritmo de FAO58"
echo "implementado en fao56Algo.php contra los valores calculados de"
echo "ETO reportados por:"
echo ""
echo "(1) Estación La Platina [01-02-2019 --- 01-02-2022]"
echo "(2) Valores reportados por el USGS para FL 2017, para el pixel 9338"
echo ""
col_echo "GRN" "(1) La Platina"

[ -d "./out" ] || mkdir out

if [ -f "./data/agrometeorologia-20220917201147.csv" ]; then
	./src/iniaDataSort.awk ./data/agrometeorologia-20220917201147.csv ./data/agrometeorologia-20220917201400.csv > src/iniaLPRun.php
	chmod 755 ./src/iniaLPRun.php
	php ./src/iniaLPRun.php > ./out/iniaLaPlatinaOut.tsv
	./src/summaryAndPlot.r --args ./out/iniaLaPlatinaOut.tsv > ./out/iniaLaPlatinaSummary.txt
	echo "Este es el resumen estadístico de los calculos realizados:"
	cat ./out/iniaLaPlatinaSummary.txt
	echo "Esta información quedó respaldada en un archivo de texto en la carpeta \"out\""
	echo "En la carpeta \"out\" se creó un gráfico Obs v/s Pred"
	mv ./out/grafico_uno_a_uno.png ./out/iniaLaPlatinaPlotPlot.png
fi

echo ""

col_echo "GRN" "(2) USGS FL"
col_echo "GRN" "Descargando datos de USGS"
echo "Fuente: https://www.usgs.gov/centers/cfwsc/science/reference-and-potential-evapotranspiration"

[ -d "./data" ] || mkdir data

col_echo "GRN" "Adquiriendo datos climáticos"
if [ ! -f "./data/Florida_2017.zip" ]; then
	wget https://fl.water.usgs.gov/et/data/2017/Florida_2017.zip -P ./data
	if [ ! -e "./data/Florida_2017.zip" ]; then
		col_echo "RED" "No fue posible descargar los datos climáticos"
		echo "Revisar: https://fl.water.usgs.gov/et/data/2017/Florida_2017.zip"
		exit 1
	fi
else
	echo "Datos climáticos adquiridos previamente"
fi

col_echo "GRN" "Adquiriendo datos de calidad y confianza de pixeles"
if [ ! -f "./data/Quality_Codes_2017.zip" ]; then
	wget https://fl.water.usgs.gov/et/data/2017/Quality_Codes_2017.zip -P ./data
	if [ ! -e "./data/Quality_Codes_2017.zip" ]; then
		col_echo "RED" "No fue posible descargar los datos de calidad"
		echo "Revisar: https://fl.water.usgs.gov/et/data/2017/Quality_Codes_2017.zip"
		exit 1
	fi
else
	echo "Datos climáticos adquiridos previamente"
fi

col_echo "GRN" "Descomprimiendo información"
if [ ! -f "./data/Florida_2017.txt" ]; then
       unzip ./data/Florida_2017.zip -d ./data/
else
	echo "Datos climáticos descomprimidos previamente"
fi
if [ ! -f "./data/Quality_Codes_2017.txt" ]; then
       unzip ./data/Quality_Codes_2017.zip -d ./data/
else
	echo "Datos de calidad descomprimidos previamente"
fi

col_echo "GRN" "Analizando la información"
./src/usgsDataSort.awk ./data/Florida_2017.txt > src/usgsFlRun.php
chmod 755 ./src/usgsFlRun.php
php ./src/usgsFlRun.php > ./out/usgsFlOut.tsv
./src/summaryAndPlot.r --args ./out/usgsFlOut.tsv > ./out/usgsFlSummary.txt
echo "Este es el resumen estadístico de los calculos realizados:"
cat ./out/usgsFlSummary.txt
echo "Esta información quedó respaldada en un archivo de texto en la carpeta \"out\""
echo "En la carpeta \"out\" se creó un gráfico Obs v/s Pred"
mv ./out/grafico_uno_a_uno.png ./out/usgsFlPlot.png

col_echo "GRN" "Proceso finalizado"
echo ""
