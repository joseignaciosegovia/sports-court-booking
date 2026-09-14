import './bootstrap';
import Alpine from 'alpinejs';

import { Calendar } from '@fullcalendar/core';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

window.FullCalendar = { Calendar, timeGridPlugin, dayGridPlugin, interactionPlugin, esLocale };