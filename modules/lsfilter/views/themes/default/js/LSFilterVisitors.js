var LSFilterMetadataVisitor = function LSFilterMetadataVisitor(){
	this.visit_entry            = function(query0)                    { return query0; };
	this.visit_query            = function(table_def1, search_query3) {
		var metadata = table_def1;
		metadata['columns'] = search_query3;
		return metadata;
		};
	this.visit_table_def_simple = function(name0)                     { return {'table':name0}; };
	this.visit_search_query     = function(filter0)                   { return filter0; };
	this.visit_filter_or        = function(filter0, filter2)          { return filter0.concat(filter2); };
	this.visit_filter_and       = function(filter0, filter2)          { return filter0.concat(filter2); };
	this.visit_filter_not       = function(filter1)                   { return filter0; };
	this.visit_filter_ok        = function(match0)                    { return match0; };
	this.visit_match_all        = function()                          { return []; };
	this.visit_match_in         = function(set_descr1)                { return []; };
	this.visit_match_field_in   = function(field0, set_descr2)        { return [field0]; };
	this.visit_match_not_re_ci  = function(field0, arg_string2)       { return [field0]; };
	this.visit_match_not_re_cs  = function(field0, arg_string2)       { return [field0]; };
	this.visit_match_re_ci      = function(field0, arg_string2)       { return [field0]; };
	this.visit_match_re_cs      = function(field0, arg_string2)       { return [field0]; };
	this.visit_match_not_eq_ci  = function(field0, arg_string2)       { return [field0]; };
	this.visit_match_eq_ci      = function(field0, arg_string2)       { return [field0]; };
	this.visit_match_not_eq     = function(field0, arg_num_string2)   { return [field0]; };
	this.visit_match_gt_eq      = function(field0, arg_num2)          { return [field0]; };
	this.visit_match_lt_eq      = function(field0, arg_num2)          { return [field0]; };
	this.visit_match_gt         = function(field0, arg_num2)          { return [field0]; };
	this.visit_match_lt         = function(field0, arg_num2)          { return [field0]; };
	this.visit_match_eq         = function(field0, arg_num_string2)   { return [field0]; };
	this.visit_set_descr_name   = function(string0)                   { return null; };
	this.visit_field_name       = function(name0)                     { return name0; };
	this.visit_field_obj        = function(name0, field2)             { return name0+"."+field2; };
	this.accept                 = function(result)                    { return result; };
	
};