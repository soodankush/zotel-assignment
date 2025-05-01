<template>
    <div id="calendar" class=" hidden m-6 p-4 bg-white rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Select Dates</h2>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <button
                    id="prev"
                    @click.stop="moveToPreviousMonth"
                    class="text-lg rounded-md font-medium px-3 py-2 border border-gray-300 hover:bg-gray-200 transition"
                >
                    &lt;
                </button>
                <div class="text-xl font-semibold">
                    {{ months[currentMonth] }} {{ currentYear }}
                </div>
            </div>

            <!-- Right Nav -->
            <div class="flex items-center gap-4">
                <div class="text-base font-semibold border-b-2 border-green-400 text-green-400 pb-1">
                    Reserved
                </div>
                <button
                    id="next"
                    @click.stop="moveToNextMonth"
                    class="text-lg rounded-md font-medium px-3 py-2 border border-gray-300 hover:bg-gray-200 transition"
                >
                    &gt;
                </button>
            </div>
        </div>
        <div class="grid grid-cols-7 gap-2">
            <div class="font-bold text-center">Su</div>
            <div class="font-bold text-center">Mo</div>
            <div class="font-bold text-center">Tu</div>
            <div class="font-bold text-center">We</div>
            <div class="font-bold text-center">Th</div>
            <div class="font-bold text-center">Fr</div>
            <div class="font-bold text-center">Sa</div>

            <!-- Example days -->
            <div v-for="(day, index) in daysOfAMonth" :key="index" class="text-center">

                <li
                    class="list-none hover:bg-gray-200 pt-2 pb-2"
                    :class="bookingDays.includes(day) ? 'bg-green-200 text-green-800 font-semibold rounded' : ''"
                    @dblclick="handleDayClick(day)"
                > {{ day }}</li>
            </div>
        </div>
        <div class="flex mt-4 justify-between items-end">
            <button
                id="Update"
                @click="submitUpdatedDates"
                class="text-lg rounded bg-green-600 text-white p-2 pl-4 pr-4 border-2 border-gray-300 hover:bg-gray-200 hover:text-gray-700"
            >
                Update Reservation
            </button>
            <button id="cancel" @click.stop="callToggle" class="text-lg rounded bg-white text-black p-2 pl-4 pr-4 border-2 border-gray-300 hover:bg-gray-200 hover:text-gray-700">Cancel</button>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, defineProps, defineEmits } from 'vue';
import {useReservationStore} from '../stores/ReservationStore.js';

function formatDate(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

const emit = defineEmits(['toggleCalendar']);

const props = defineProps({
    id: {
        type: Number,
        required: true
    },
    reservationFrom: {
        type: String,
        required: true,
    },
    reservationTill: {
        type: String,
        required: true,
    },
})

const today = new Date();
const selectedFromDate = ref(null)
const selectedTillDate = ref(null)
const selectionStage = ref('from')
const errorMessage = ref('')
const reservationStore = useReservationStore();


let currentMonth = new Date(props.reservationFrom).getMonth();
let currentYear = new Date(props.reservationFrom).getFullYear();

const months = ref([
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]);

let daysOfAMonth = ref([]);
let parsedDateFrom = new Date(props.reservationFrom);
let parsedDateTill = new Date(props.reservationTill);
let fromDate = {
    'day': parsedDateFrom.getDay(),
    'date': parsedDateFrom.getDate(),
    'month': parsedDateFrom.getMonth(),
    'year': parsedDateFrom.getFullYear()
}

let tillDate = {
    'day': parsedDateTill.getDay(),
    'date': parsedDateTill.getDate(),
    'month': parsedDateTill.getMonth(),
    'year': parsedDateTill.getFullYear()
}

let bookingDays = ref([]);
const generateCalendar = (month, year) => {
    bookingDays.value = [];
    let firstDay = new Date(year, month, 1).getDay();
    let noOfDaysInMonth = new Date(year, month + 1, 0).getDate();

    for (let i=0; i < firstDay; i++) {
        daysOfAMonth.value.push('');
    }

    for (let j=1; j <= noOfDaysInMonth; j++) {
        daysOfAMonth.value.push(j);
        const currentDate = new Date(currentYear, currentMonth, j);
        const from = new Date(fromDate.year, fromDate.month, fromDate.date);
        const till = new Date(tillDate.year, tillDate.month, tillDate.date);

        if (currentDate >= from && currentDate <= till) {
            bookingDays.value.push(j);
        }
    }

}

const moveToPreviousMonth = () => {
    daysOfAMonth.value = [];
    currentMonth--;
    if(currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentMonth, currentYear);
}

const moveToNextMonth = () => {
    daysOfAMonth.value = [];
    currentMonth++;
    if(currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentMonth, currentYear);
}

const callToggle = () => {
    emit('toggleCalendar');
}

const handleDayClick = (day) => {
    if (!day) return;

    const selectedDate = new Date(currentYear, currentMonth, day);

    if (selectionStage.value === 'from') {
        selectedFromDate.value = selectedDate;
        selectedTillDate.value = null;
        selectionStage.value = 'till';
    } else if (selectionStage.value === 'till') {
        if (selectedDate < selectedFromDate.value) {
            errorMessage.value = 'Check-out must be after Check-in.';
            return;
        }
        selectedTillDate.value = selectedDate;
        errorMessage.value = '';
        selectionStage.value = 'from';

        highlightSelectedRange();
    }
};

const highlightSelectedRange = () => {
    bookingDays.value = [];

    if (!selectedFromDate.value || !selectedTillDate.value) return;

    let current = new Date(selectedFromDate.value);
    while (current <= selectedTillDate.value) {
        if (current.getMonth() === currentMonth && current.getFullYear() === currentYear) {
            bookingDays.value.push(current.getDate());
        }
        current.setDate(current.getDate() + 1);
    }
};

const submitUpdatedDates = async () => {
    if (!selectedFromDate.value || !selectedTillDate.value) {
        errorMessage.value = 'Please select both check-in and check-out dates.';
        return;
    }

    if (selectedTillDate.value < selectedFromDate.value) {
        errorMessage.value = 'Check-out must be after Check-in.';
        return;
    }

    try {
        const res = await axios.post(`/api/update-dates/${props.id}`, {
            check_in: formatDate(selectedFromDate.value),
            check_out: formatDate(selectedTillDate.value),
        });

        alert('Reservation updated successfully.');

        emit('toggleCalendar', {
            id: props.id,
            check_in: formatDate(selectedFromDate.value),
            check_out: formatDate(selectedTillDate.value),
        });

    } catch (err) {
        errorMessage.value = err.response?.data?.error || 'Something went wrong while updating.';
        console.error(err);
    }
};

onMounted(() => {
    generateCalendar(currentMonth, currentYear);
})


</script>
