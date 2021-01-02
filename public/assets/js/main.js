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
import bars from './apps/bars'


window.onload = function() {
  
	initVueComponent(bars, '#bars');
};