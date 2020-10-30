create table node_tree(
	idNode int,
	level int,
	iLeft int,
	iRight int,
	primary key(idNode)
);

create table node_tree_names(
	idNode int,
	language varchar(7),
	NodeName varchar(32),
	primary key(idNode, language),
	foreign key(idNode) references node_tree(idNode)
);