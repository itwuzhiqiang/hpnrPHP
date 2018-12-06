function getEntityModel(returnTypeNameSpace, returnTypeClass) {
	returnTypeClass = returnTypeClass.replace(/_/gm, '');
	return 'IDE_Entity_' + returnTypeClass + '_Model';
}

function getCptModel(returnTypeNameSpace, returnTypeClass) {
	returnTypeClass = returnTypeClass.replace(/_/gm, '');
	return 'IDE_Cpt_' + returnTypeClass + '_Model';
}

function getCptEntityModel(returnTypeNameSpace, returnTypeClass) {
	returnTypeClass = returnTypeClass.replace(/_/gm, '');
	return 'IDE_Cpt_' + returnTypeClass + '_Model';
}
