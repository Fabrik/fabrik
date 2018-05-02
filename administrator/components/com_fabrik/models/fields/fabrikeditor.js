/**
 * Admin Fabrik Ace Editor
 *
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,Asset:true */

"use strict";

define(["fab/fabrik", "admin/namespace", "lib/ace/src-min-noconflict/ace"], function (Fabrik, FabrikAdmin) {
	FabrikAdmin.model.fields.fabrikeditor = FabrikAdmin.model.fields.fabrikeditor || {};

	var FabrikEditorElement = new Class ({
		Implements: [Options, Events],

		options: {
			theme : 'github',
			mode  : 'php',
			height: '142px',
			width : '100%',
		},

		initialize: function (id, options) {
			this.setOptions(options);

			var field = document.getElementById(id);
			if (field !== null) {
				this.initializeAce(id, options);
			} else {
				if (id.includes('X__')) {
					// Likely to be because the element this has been called on is the hidden Joomla subform template (which is sneakily stored in a script node).
					// This is, however, removed if plugin is loaded by ajax so we cannot test for it.
					var p = id.split('__');
					for (var i = p.length - 1; i > 0; i--) {
						if (p[i].slice(-1) === 'X' && p[i-1] === p[i].slice(0, -1)) {
							return;
						}
					}
				}
				fconsole('fabrikeditor: unable to find element to initialise:',id);
			}
		},

		initializeAce: function(id, options) {
			this.id = id;
			var field = document.getElementById(id);

			// Check if we have parent divs already exist because e.g. element has been cloned
			var parent = field.parentElement, containerDiv, aceDiv;
			if (parent.get('tag') !== "div" || parent.id.slice(-13) !== "-aceContainer") {
				// Add parent and sibling containers)
				containerDiv = document.createElement("div");
				containerDiv.id = id + "-aceContainer";
				aceDiv = document.createElement("div");
				aceDiv.id = id + "-ace";
				containerDiv.appendChild(aceDiv);
				containerDiv = parent.insertBefore(containerDiv, field);
				containerDiv.appendChild(field);
			}
			// pluginmanager.js renames names/ids when you delete a preceding plugin which breaks ace
			// so we need to keep ace-ids intact and avoid issues with duplicate ids by:
			//	 adding a random string to the id where ace needs id to be kept the same; and
			//	 save dom object for textarea so that change of id doesn"t break it.
			var aceId = id + "_" + ("00000" + (Math.random() * 0xffffff).toString(16)).slice(-6);
			containerDiv = field.parentElement;
			containerDiv.id = aceId + "-aceContainer";
			// We can't know the true dimensions of the editor until it is instantiated and we get access to the internals
			// so this is an estimate which will be updated by onrender as soon as ace is rendered
			var h = this.options.height.slice(-2) === 'px' ? this.options.height : this.options.height.toInt() * 28 + 2;
			containerDiv.style = "position:relative; width:" + this.options.width + "; height:" + h + ";";
			aceDiv = containerDiv.querySelector("[id$='-ace']");
			if (!aceDiv) {
				fconsole("FabrikEditor: cannot find ace container to randomise", id + "-ace");
				return;
			}
			aceDiv.id = aceId + "-ace";
			aceDiv.className += "FbEditorAce";
			var FbEditor = ace.edit(aceId + "-ace");
			FbEditor.setTheme("ace/theme/" + this.options.theme);
			var aceMode = this.options.mode === "php" ? {path:"ace/mode/php", inline:true} : "ace/mode/" + this.options.mode;
			FbEditor.getSession().setMode(aceMode);
			FbEditor.$blockScrolling = Infinity;
			FbEditor.setValue(field.value);
			FbEditor.navigateFileStart();
			FbEditor.setAnimatedScroll(true);
			FbEditor.setBehavioursEnabled(true);
			FbEditor.setDisplayIndentGuides(true);
			FbEditor.setHighlightGutterLine(true);
			FbEditor.setHighlightSelectedWord(true);
			FbEditor.setShowFoldWidgets(true);
			FbEditor.setWrapBehavioursEnabled(true);
			FbEditor.getSession().setUseWrapMode(true);
			FbEditor.getSession().setTabSize(2);
			FbEditor.on("blur", function () {
				if (field.value !== FbEditor.getValue()) {
					field.value = FbEditor.getValue();
					field.fireEvent("change", field);
				}
				field.fireEvent("blur", field);
			});
			FbEditor.setAutoScrollEditorIntoView(true);
			this.updateHeight = function () {
				var s = FbEditor.getSession();
				var r = FbEditor.renderer;
				var l = s.getScreenLength();
				var h = l * r.lineHeight
					  + (r.$horizScroll ? r.scrollBar.getWidth() : 0)
					  + 2;
				var minH = this.options.height.slice(-2) === 'px'
					? this.options.height.toInt()
					: this.options.height * r.lineHeight
					  + (r.$horizScroll ? r.scrollBar.getWidth() : 0)
					  + 2;
				h = h < minH ? minH : h;
				var c = document.getElementById(aceId + "-aceContainer");
				if (c.getStyle("height").toInt() !== h) {
					c.setStyle("height", h.toString() + "px");
					FbEditor.resize();
				}
			}.bind(this);
			this.updateHeight();
			FbEditor.renderer.on("afterRender", function() {
				this.updateHeight();
			}.bind(this));
			FbEditor.getSession().on("change", this.updateHeight);

			if (Fabrik.debug) {
				fconsole('fabrikeditor: initialised', aceId);
			}
			return;
		},

		// Called from repeatgroup add and from namespace to handle Joomla subform cloning
		cloned: function (newid, counter) {
			if (Fabrik.debug) {
				fconsole('fabrikeditor: cloned', newid);
			}
			this.initializeAce(newid, this.options);
		},

	});

	window.FabrikEditorElement = FabrikEditorElement;
	return FabrikEditorElement;
});
