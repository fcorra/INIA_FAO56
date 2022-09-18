# Análisis de los datos de Evapotranspiración de Referencia reportados por la Red Agrometerológica de INIA 

## Contexto:

La red Agrometeorología de INIA reporta datos climáticos para distintas
variables a intervalos anuales, mensuales, diarios u horarios. Además de
reportar los datos medidos en sus estaciones, pone a disposición de los usuarios
conjuntos de datos calculados a partir de distintos modelos ambientales. Uno de
estos conjuntos de datos es la evapotranspiración de referencia (ET0), calculada
de acuerdo al modelo propuesto por [FAO][1].

## Problema:
Varios usuarios de la red de Agrometeorología de INIA indican que los valores
calculados de evapotranspiración de referencia no concuerdan con lo que es
posible estimar a partir del algoritmo de [FAO][1].

## Pregunta:

¿Estará bien implementado el algoritmo de [FAO][1] en la plataforma de INIA?

## Metodología:

Se propone implementar el algoritmo de [FAO][1] y validarlo contra una estación
externa a la red de INIA que sea confiable. Validado el algoritmo, se propone
utilizarlo para predecir ET0 con los datos de una estación de INIA y comparar
los valores predichos por él contra los valores calculados por INIA:

1. Implementar el algoritmo de FAO para el cálculo de ET0 a escala diaria en
   PHP.
2. Buscar una estación de referencia, que esté fuera de la red y que reporte,
   además de los datos climáticos, valores calculados de ET0.
3. Comparar los valores predichos por el algoritmo implementado y los valores
   reportados por la estación de referencia.
4. Utilizar el algoritmo para determinar ET0 de acuerdo a los datos climáticos
   entregados por la red de Agrometeorología.
5. Comparar los valores predichos por el algoritmo implementado y los valores
   reportados por la red de INIA.

El archivo ejecutable *run.sh* realiza estos pasos.

## Detalles:

### Software utilizado

* Linux L0809 5.10.0-18-amd64 #1 SMP Debian 5.10.140-1 (2022-09-02) x86\_64 GNU/Linux
* GNU bash, version 5.1.4(1)-release (x86\_64-pc-linux-gnu)
* PHP 7.4.30 (cli) (built: Jul  7 2022 15:51:43) ( NTS )
* GNU Awk 5.1.0, API: 3.0 (GNU MPFR 4.1.0, GNU MP 6.2.1)
* R version 4.0.4 (2021-02-15) -- "Lost Library Book"

### Algoritmo

Se implementó el algoritmo propuesto por [FAO][1] como una clase en PHP. La clase
se llama METEO\_DATA y tiene los siguientes valores de iniciación:

```php
$MD = new METEO_DATA (
		array $latitud, 
		array $altitud,
		array $albedo,	
		array $fecha, 
		array $radiacion_solar, 
		array $temp_min, 
		array $temp_max, 
		array $humedad_relativa, 
		array $velocidad_del_viento
) 
```

Al iniciarse, el objeto realiza todos los cálculos para determinar ET0 y
almacena los valores intermedios (radiación neta, neta de onda corta, de onda
larga, etc) en una colección asociativa (*Data*).

La clase tiene los siguientes atributos públicos:

```php
$MD->Latitud;
$MD->Altitud;
$MD->Data;
```

*Data* corresponde a una colección asociativa 2D que comprende las siguientes
colecciones indexadas:

* "Fecha"
* "Radiación solar"
* "Temperatura mínima"
* "Temperatura máxima"
* "Humedad relativa media"
* "Velocidad del viento"
* "Radiación extraterrestre"
* "Radiación solar día despejado"
* "Radiación neta solar"
* "Presión de vapor a saturación"
* "Presión real de vapor"
* "Radiación neta de onda larga"
* "Radiación neta"
* "Déficit de presión de vapor"
* "Pendiente presión de vapor vs temperatura"
* "Flujo de calor del suelo"
* "Evapotranspiración de referencia"

La clase solo tiene un método público que devuelve una colección con los valores
calculados de ET0 (*$MD-\>Data["Evapotranspiración de referencia"]*)

```php
$MD->print () : array
```

### Estación de referencia

La confianza es algo relativo a las experiencias del sujeto. Al respecto, yo creo
en la seriedad y validación del trabajo que realiza The United States Geological
Survey (USGS). Esta organización entrega mapas con los valores de ET0 para los Estados
Unidos. El Caribbean-Florida Water Science Center (CFWSC) reporta valores de
evapotranspiración potencial y de referencia para el estado de Florida. Propongo
usar esos datos para validar el algoritmo.

Los datos están disponibles en [este enlace][2] y presentan la siguiente
información por columnas, en un archivo *.txt* separado por *tab* usando punto decimal:

* date --> Date data representation
* latitude --> Latitude of Pixel value
* longitude --> Longitude of Pixel value
* pixel --> Pixel ID number
* PET --> Potential ET(mm/day)
* RET --> Reference ET(mm/day)
* solar --> Solar Radiation - Daily Insolation (MegaJoules/sq meter/day)
* RHmax --> Maximum Relative Humidity for day (%)
* RHmin --> Minimum Relative Humidity for day (%)
* Tmax --> Maximum Temperature for day (C)
* Tmin --> Minimum Temperature for day (C)
* Wind --> Wind Speed (meters/second)

Junto a la información de cada pixel, es posible descargar el METADATO
correspondiente y la información de la calidad de la información entregada.

La información de calidad responde a qué tan buenos eran los datos de radiación
solar medidos para ese día, siendo 1 la puntuación más alta y 4 la más baja.

Para realizar la validación **no** usaré la información de todos los pixeles,
sino solo la del pixel **ID = 9338**. ¿Por qué? Bueno, porque había que
elegir uno. El valor del pixel está fijo en la serie de instrucciones para AWK
que digiere los datos crudos de USGS.

La Latitud del pixel 9338 corresponde a 24.59, indicada en la columna número 2
del fichero de datos. El CFWSC no reporta la elevación de ninguno de los
pixeles. Sin embargo, una visita a [google.earth][3], indica que para la
coordenada [24.59, -83.113] la elevación está, por lo general, bajo los 10msnm.

Como el CFWSC no indica la elevación este dato está fijo, de acuerdo a lo que
revisé en [google.earth][3], en las instrucciones que ejecuta AWK y, por tanto,
en la serie de instrucciones de PHP que éste archivo genera.

### Estación INIA

De la [Red Agrometeorológica de INIA][4] se escogió la estación La Platina, La
Pintana, INIA (La Platina). Como la Red Agrometeorológica no permite la descarga
de datos directamente por consultas http, https, etc, y tampoco cuenta con una
API, se descargaron manualmente dos archivos en formato *.csv* (esto quiere
decir que no son descargados automáticamente por *run.sh*):

* agrometeorologia-20220917201147.csv
* agrometeorologia-20220917201400.csv 

El archivo agrometeorologia-20220917201147.csv cuenta con datos de "Temperatura del
Aire Mínima", "Temperatura del Aire Máxima", "Radiación Solar", "Velocidad del
Viento" y "Humedad Relativa".

El archivo agrometeorologia-20220917201400.csv contiene los datos de ET0, que deben
ser consultados por separado. 

Se consideró un intervalo de un año, con datos a escala diaria, medidos entre el
01-02-2019 y el 01-02-2020.

### Flujo general e instrucciones

Esta carpeta cuenta con el archivo principal de instrucciones *run.sh* y con dos
subcarpetas:

* src
* data

**SRC**

La carpeta *src* contiene las secuencias de instrucciones utilizadas por
*run.sh*. Estas son:

* fao56Alg.php --> implementación en PHP del algoritmo de FAO.
* usgsDataSort.awk --> instrucciones para AWK que genera el script de PHP que
  calculará ET0 para el pixel 9338 con los datos del CFWSC.
* iniaDataSort.awk --> instrucciones para AWK que genera el script de PHP que
  calculará ET0 con los datos de la estación La Platina de INIA.
* summaryAndPlot.r --> instrucciones para R que calculan las estadísticas
  descriptivas de los datos (RMSE, SSD, otros).

Las instrucciones de AWK generarán los siguientes archivos (intermedios) de PHP
(*run.sh* no borra los archivos intermedios dado que, a veces, sirven como
diagnóstico):

* usgsFlRun.php --> cálculo de ET0 para el pixel 9338 de CCFWSC.
* iniaLPRun.php --> cálculo de ET0 para la estación La Platina de INIA.

**DATA**

La carpeta *data* contiene los archivos de datos necesarios para realizar los
cálculos. Como el CFWSC soporta acceder a los datos por http, https, etc, los
datos no están incluidos en esta carpeta y son descargados la primera vez que se
ejecuta la serie de instrucciones *run.sh*.

Los archivos que contiene la carpeta *data* son (después del trabajo de descarga
de *run.sh*):

* Florida\_2017.zip --> archivo comprimido del CFWSC que contiene
  Florida\_2017.txt
* Quality\_Codes\_2017.zip --> archivo comprimido del CFWSC que contiene
  Quality\_codes\_2017.text
* Florida\_2017.txt --> archivo *txt* separado por *tab* del CFWSC con datos
  climáticos para florida.
* Quality\_Codes\_2017.txt --> archivo *txt* separado por *tab* del CFWSC que
  contiene la calidad de los datos de radiación solar capturados para una
determinada fecha.
* agrometeorologia-20220917201147.csv --> archivo csv con datos meteorológicos para
la estación La Platina de la Red Agroclimática de INIA.
* agrometeorologia-20220917201400.csv --> archivo csv con los valores calculados de
ET0 para la estación La Platina por la Red Agroclimática de INIA.

**OUT**

Tras su ejecución, el script *run.sh* generará una carpeta con los resultados
obtenidos. Esta carpeta contendrá los siguientes archivos:

* iniaLaPlatinaOut.tsv -->  --> resultados de la estimación de ET0 utilizando
  fao56Alg.php (Pred) y valores reportados por la red Agrometeorología para la
estación La Platina (Obs).
* iniaLaPlatinaPlot.png --> gráfico de valores observados (red Agrometeorología)
  v/s predichos (fao56Alg.php)
* iniaLaPlatinaSummary.txt --> estadística descriptiva de valores observados
  (red Agrometeorología) y predichos (fao56Alg.php) para la estación La Platina.
* usgsFlOut.tsv --> resultados de la estimación de ET0 utilizando fao56Alg.php
  (Pred) y valores reportados por el CFWSC para el pixel 9338.
* usgsFlPlot.png --> gráficos de valores observados (CFWSC) v/s predichos
  (fao56Alg.php)
* usgsFlSummary.txt --> estadística descriptiva de valores observados (CFWS) y
  predichos (fao56Alg.php) para el pixel 9338.

## Conclusión:

El RMSE del algoritmo implementado con la estación de referencia es de 0.26mm,
mientras que con la estación La Platina es de 1.09mm. La estación de INIA tiende a
subestimar los valores de ET0 (m = 0.77).

[1]: https://www.fao.org/3/x0490s/x0490s.pdf
[2]: https://www.usgs.gov/centers/cfwsc/science/reference-and-potential-evapotranspiration
[3]: https://earth.google.com/web/search/24.59,+-82.113/@24.59,-82.113,4.13867436a,950.27785045d,35y,0h,45t,0r/data=ClIaKBIiGdejcD0KlzhAIawcWmQ7h1TAKg4yNC41OSwgLTgyLjExMxgCIAEiJgokCbgBOZ9_mThAEVJQpXHFlThAGUYFbA3ghlTAIb92VS_sh1TAKAI
[4]: https://agrometeorologia.cl/


