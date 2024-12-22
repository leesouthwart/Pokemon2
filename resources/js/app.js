import './bootstrap';
//
// import Alpine from 'alpinejs';
//
// window.Alpine = Alpine;
//
// Alpine.start();
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

var notyf = new Notyf();

Livewire.on('success', message => {
    console.log(message);
    notyf.success(message[0]);
})