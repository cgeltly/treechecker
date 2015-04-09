library(DBI)
library(ggplot2)

args <- commandArgs(TRUE)

con <- dbConnect(RMySQL::MySQL(),
	dbname='treechecker', 
	user='treechecker',
	password='',
	host='localhost', 
	port=3306)

# Read the lifespans
d <- dbReadTable(conn = con, name = 'lifespans')
d <- subset(d, gedcom_id == args[1])

# Update birth column; take the year and set it as numeric
d <- transform(d, 
	birth = as.numeric(substr(d$birth, 0, 4)), 
	sex = factor(d$sex))

# Plot the linear model as a trendline
p <- qplot(birth, lifespan, data=d, color=sex)
p <- p + geom_smooth(method = "lm")
filename <- paste("lifespan", args[1], ".svg", sep="")
ggsave(file=filename, plot=p, width=10, height=6)
