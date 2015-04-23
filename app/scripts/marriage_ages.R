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
d <- dbReadTable(conn = con, name = 'marriage_ages')
d <- subset(d, gedcom_id == args[1])

# Update birth column; take the year and set it as numeric
d <- transform(d, 
	indi_birth = as.numeric(substr(d$indi_birth, 0, 4)), 
	indi_sex = factor(d$indi_sex))

# Plot the linear model as a trendline
p <- qplot(indi_birth, marriage_age, data=d, color=indi_sex)
p <- p + geom_smooth(method = "lm")

# Save the result as .svg
filename <- paste("marriages_age", args[1], ".svg", sep="")
ggsave(file=filename, plot=p, width=10, height=6)
