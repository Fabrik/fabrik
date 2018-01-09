function alertObjectProperties(thisObject) {
	var propertyName, thisList;
	for (propertyName in thisObject) {
		thisList = thisList + "[" + propertyName + "] -- [" + thisObject[propertyName] + "]\n";
	}
	alert(thisList);
}


/**
 * The form has just been loaded or the user has just chosen a division of a year:
 * -- division possibilities are: month, quarter, third, half & season
 * -- change the label on the "_value" element
 * -- update "_value" element with the value that may have been previously saved
 * 
 * @param {Object} thisElement - Fabrik element that called us via onLoad or onClick.
 */
function commonDateDivisionType(thisElement) {
	var thisForm, elementFullname, elementName;
	var tableName, tabName, nameRoot, basicName;
	var valueLabel, valueName;
	var suffixes, suffix;
	var saverName, saverValue, activeName, activeValue;
	
	thisForm = thisElement.form;
	elementFullname = String(thisElement.options.fullName);
	elementName = elementFullname.match(/[a-zA-Z0-9]+___(\w+)/)[1];
	
	tableName = elementFullname.match(/(\w+)___\w+/)[1];
	tabName = elementName.match(/([a-zA-Z0-9]+)_\w+/)[1];
	nameRoot = tableName + '___' + tabName;
	
	basicName = elementName.match(/[a-zA-Z0-9]+_(\w+)/)[1];
	basicName = basicName.match(/([a-zA-Z0-9]+)_\w+/)[1];
	
	valueLabel = String(thisForm.elements.get(elementFullname).getValue());
	valueName = valueLabel.toLowerCase(); //month
	
	suffixes = ['value', 'accuracy', 'confidence'];
	suffixes.forEach(function(suffix) { 
		saverName  = nameRoot + '_saver_' + valueName + '_' + suffix;
		activeName = nameRoot + '_' + basicName + '_' + suffix;
		saverValue = thisForm.elements.get(saverName).getValue();
		if (suffix == 'value') {
			thisForm.elements.get(activeName).setLabel(valueLabel);
		}
		thisForm.elements.get(activeName).update(saverValue);
	});
	
	return;
}


/**
 * The form has just been loaded or the user has just chosen a value
 * for this division of a year:
 * -- division possibilities are: week, month*, quarter, third, half & season -- (* = default)
 * -- stow away that value in the corresponding "_saver_" element
 * 
 * @param {Object} thisElement - Fabrik element that called us via onLoad or onClick.
 */
function commonDateDivisionValue(thisElement) {
	var thisForm, elementFullname, elementName;
	var tableName, tabName, nameRoot, basicName;
	var typeFullname, typeName;
	var suffixes, suffix;
	var saverName, activeName, activeValue;
	
	thisForm = thisElement.form;
	elementFullname = String(thisElement.options.fullName);
	elementName = elementFullname.match(/[a-zA-Z0-9]+___(\w+)/)[1];
	
	tableName = elementFullname.match(/(\w+)___\w+/)[1];
	tabName = elementName.match(/([a-zA-Z0-9]+)_\w+/)[1];
	nameRoot = tableName + '___' + tabName;
	
	basicName = elementName.match(/[a-zA-Z0-9]+_(\w+)/)[1];
	basicName = basicName.match(/([a-zA-Z0-9]+)_\w+/)[1];
	
	typeFullname = nameRoot + '_' + basicName + '_type';
	typeName = String(thisForm.elements.get(typeFullname).getValue());
	typeName = typeName.toLowerCase(); //month
	
	suffixes = ['value', 'accuracy', 'confidence'];
	suffixes.forEach(function(suffix) {
		saverName   = nameRoot + '_saver_' + typeName + '_' + suffix;
		activeName  = nameRoot + '_' + basicName + '_' + suffix;
		activeValue = thisForm.elements.get(activeName).getValue();
		thisForm.elements.get(saverName).update(activeValue);
	})
}


/**
 * Show/hide combinations of the Fabrik groups (and their tabs) used to develop
 * a date or duration based on the chosen date type (single date, start/end,
 * start/duration, duration/end, duration)
 * 
 * @param {Object} thisElement - Fabrik element that called us via onLoad or onClick.
 *
 * @todo get list of groups plus new function to loop array of 3 "hide/show"s
 *       for choice switch below
 * @todo change "Start Date" to "Date" when "Single Date" is chosen 
 */
function commonDateType(thisElement) {
	var thisForm = thisElement.form;
	var elementFullname = String(thisElement.options.fullName);
	
	var chosen = thisForm.elements.get(elementFullname).getValue();
	
	var choice1 = 'Single Date';
	var choice2 = 'Start/End';
	var choice3 = 'Start/Duration';
	var choice4 = 'Duration/End';
	var choice5 = 'Duration';
	
	var tabGroupA = '#group118_tab';
	var tabGroupB = '#group122_tab';
	var tabGroupC = '#group121_tab';
	var tabGroupX = '#group140_tab';
	
	jQuery(tabGroupX).hide();
	
	switch (chosen) {
	case choice1:
		jQuery(tabGroupA).show();
		jQuery(tabGroupB).hide();
		jQuery(tabGroupC).hide();
		jQuery(tabGroupA).trigger('click');
		break;
	case choice2:
		jQuery(tabGroupA).show();
		jQuery(tabGroupB).hide();
		jQuery(tabGroupC).show();
		jQuery(tabGroupA).trigger('click');
		break;
	case choice3:
		jQuery(tabGroupA).show();
		jQuery(tabGroupB).show();
		jQuery(tabGroupC).hide();
		jQuery(tabGroupA).trigger('click');
		break;
	case choice4:
		jQuery(tabGroupA).hide();
		jQuery(tabGroupB).show();
		jQuery(tabGroupC).show();
		jQuery(tabGroupB).trigger('click');
		break;
	case choice5:
		jQuery(tabGroupA).hide();
		jQuery(tabGroupB).show();
		jQuery(tabGroupC).hide();
		jQuery(tabGroupB).trigger('click');
		break;
	}
	return;
}

/**
 * Formatting based on chosen calendar type.
 * 
 * @param {Object} thisElement - The Fabrik element calling us via onLoad or onClick.
 * @param {string} tabName - "start", "duration", or "end"
 * @todo Get Fourmilab calendar working
 */
/*
function commonDateCalendarType(thisElement, tabName) {
	return; // "beat it" - until rest of function is ready to be tested
	var thisForm, table, baseName, lineName
	var calendar, calendars
	
	thisForm = thisElement.form;
	table = 'gn_event___' + tabName;
	baseName = table + '_date';
	lineName = baseName + '_division';
	
	calendars = baseName + '_calendar_type';
	calendar = thisForm.elements.get(calendars).getValue();
	switch(calendar) {
		case 'iso-edtf' :
			// change division & subdiv headers
			break;
		case 'iso-yd' :
			// hide the division subgroup
			// set the number of days for dropdown to ~365
			break;
		case 'iso-ywd' :
			// hide the division subgroup
			// set the number of weeks for dropdown to ~52
			// set the number of days for dropdown to 7
			// set division header to "--- Week"
			break;
		case 'julian' :
			break;
	}
}
*/

/**
 * describe the function.
 * 
 * @param {Object} thisElement - Fabrik element that called us via onLoad or onClick.
 * @param {string} tabName - "start", "duration", or "end"
 */
/*
function commonDateLineShowHide(thisElement, tabName, line, action) {
	var thisForm = thisElement.form;
	var table, tableName, baseName, lineName, elems;

	tableName = thisForm.options.primaryKey.match(/(\w+)___\w+/)[1];
	table = tableName + tabName; baseName = table + '_date';
	lineName = baseName + '_' + line;
	elems[] = baseName + '_header';
	elems[] = table + '_spacer_4a';
	elems[] = table + '_spacer_4b';
	elems[] = table + '_spacer_5b';
	elems[] = lineName;
	elems[] = lineName + '_accuracy';
	elems[] = lineName + '_confidence';
	for (i =0, i < elems.length, i++) {
		thisForm.elements.get(elems[i].id).hide();
	}
}
*/



 