#!/usr/bin/R --vanilla --no-echo -f
args <- commandArgs(TRUE)

db <- read.table (args[1], header=TRUE, sep = "\t", dec=".")

my_range <- range (c(db$Obs, db$Pred))
minmin <- min (my_range)
maxmax <- max (my_range)

png ("out/grafico_uno_a_uno.png")
par (pty = "s")
plot (db$Pred ~ db$Obs, 
      xlab = "Observed",
      ylab = "Predicted", 
      ylim = c(minmin, maxmax),
      xlim = c(minmin, maxmax)
      )
abline(0, 1, col = "red", lty = 2)
dev.off()

print ("Resumen estadístico de los valores de la estación:")
print (summary(db$Obs))
print ("Resumen estadístico de los valores predicho:")
print (summary(db$Pred))

ss = sum ((db$Obs - db$Pred)^2)
rmse = sqrt (ss / length (db$Obs))

print (paste ("Suma de cuadrados: ", round (ss, 2), sep = ""))
print (paste ("RMSE: ", round (rmse, 2), sep = ""))

