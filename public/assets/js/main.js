// Build CSS
import '../css/app.css'


function initVueComponent(component, el) {
	//console.log(el);
  if(document.querySelectorAll(el).length > 0) {
    component.el = el;
    new Vue(component);
  }
}

//import alpine_init from './alpine/init-alpine'
import upload_file from './apps/upload_file'
import modal from './apps/modal'
import bars from './apps/bars'
import tables from './apps/tables'

//var vm;

window.onload = function() {
  
	initVueComponent(bars, '#bars');
	/*if(document.querySelectorAll('#bars').length > 0) {
	    bars.el = '#bars';
	    vm = new Vue(bars);
		window.vm = vm;
	}*/
	initVueComponent(tables, '#myTable');
};