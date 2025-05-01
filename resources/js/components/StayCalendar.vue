<template>
    <div class="bg-gray-100 p-6 rounded-lg shadow-lg">
        <p class="mb-5 font-bold text-4xl">Stay Calendar</p>
        <div class="flex flex-wrap justify-between items-center gap-4 mb-4">

            <!-- Left: Navigation & Date Picker -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Prev Button -->
                <button @click.stop="prevFunction" class="rounded border px-3 py-2 text-gray-700 hover:bg-gray-200">&lt;</button>

                <!-- Date Range Button -->
                <div class="relative">
                    <button
                        @click="isPickerOpen = !isPickerOpen"
                        class="flex items-center gap-2 bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600"
                    >
                        <div class="text-left">
                            <div class="text-xs uppercase">From</div>
                            <div class="text-sm font-semibold">{{ fromDate || 'Select' }}</div>
                        </div>
                        <span class="text-xl">→</span>
                        <div class="text-left">
                            <div class="text-xs uppercase">To</div>
                            <div class="text-sm font-semibold">{{ toDate || 'Select' }}</div>
                        </div>
                    </button>

                    <!-- Date Inputs -->
                    <div v-if="isPickerOpen" class="absolute z-10 bg-white p-4 mt-2 rounded shadow-md border">
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-1">From Date</label>
                                <input
                                    type="date"
                                    v-model="fromDate"
                                    @change="validateDates"
                                    class="w-full border px-3 py-2 rounded text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">To Date</label>
                                <input
                                    type="date"
                                    v-model="toDate"
                                    @change="validateDates"
                                    class="w-full border px-3 py-2 rounded text-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today & Next -->
                <button @click.stop="updateToday()" class="rounded border px-4 py-2 text-gray-700 bg-white hover:bg-gray-200">Today</button>
                <button @click.stop="nextFunction" class="rounded border px-3 py-2 text-gray-700 hover:bg-gray-200">&gt;</button>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex gap-3">
                <button class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">Add Booking</button>
                <button class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">Unallocated: 6</button>
            </div>
        </div>

        <!-- Weekdays -->
        <div class="overflow-x-auto">
            <div class="grid grid-cols-[150px_repeat(14,minmax(100px,1fr))] bg-gray-100 text-center text-sm font-semibold">
                <div class="p-2 border-r">Room</div>
                <div v-for="date in allDates" :key="date.date" class="p-2 border-r">
                    <div class="uppercase text-xs text-gray-600">{{ date.dayName }}</div>
                    <div>{{ date.date }}</div>
                </div>
            </div>

            <!-- Loop Room Types -->
            <div v-for="(section, index) in reservationData" :key="index">
                <!-- Room Type Header -->
                <div class="bg-gray-200 text-left text-green-600 font-bold px-4 py-2 uppercase">
                    {{ section.type }}
                </div>

                <div
                    v-for="(dates, roomNo) in section.room_data"
                    :key="roomNo"
                    class="grid grid-cols-[150px_repeat(14,minmax(100px,1fr))] text-sm"
                >
                    <div class="p-2 border-r font-medium bg-white">{{ roomNo }}</div>

                    <div
                        v-for="(data, date) in dates"
                        :key="date"
                        class="p-1 text-center border-r"
                        :class="{
                            'bg-blue-500 text-white truncate': data,
                            'bg-white text-gray-500': !data
                        }"
                        @click="openModal(data)"
                    >
                        <span v-if="data">{{ data.customer_name }}</span>
                        <span v-else><button>Book Now</button></span>
                    </div>
                </div>
            </div>
        </div>

        <Modal
            v-if="isDialogOpen"
            :reservationData="currentData"
            @close="closeModal"
        />
    </div>
</template>
<script setup>
import Modal from "./Modal.vue";
import {useReservationStore} from "../stores/ReservationStore.js";
import {watch, onMounted, ref} from "vue";

function getDefaultReservationRange(addDays) {
    const today = new Date();
    const till = new Date();
    till.setDate(today.getDate() + addDays);

    return {
        from_date: today.toISOString().slice(0, 10),
        till_date: till.toISOString().slice(0, 10)
    };
}

function validateDates(){
    if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
        toDate.value = null;
        alert("To date cannot be greater than From Date");
        isPickerOpen.value = true;
        return false;
    } else if(fromDate.value && toDate.value && fromDate.value < toDate.value) {
        isPickerOpen.value = false;
        return true;
    }
    else {
        isPickerOpen.value = true;
        return false;
    }
}

function getDaysForDateRange(from, till) {
    const date1 = new Date(from);
    const date2 = new Date(till);
    allDates.value = [];

    let currentDate = new Date(date1);

    while (currentDate <= date2) {
        const month = currentDate.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        const day = currentDate.getDate();

        allDates.value.push({
            date: `${month} ${day}`,
            dayName: currentDate.toLocaleDateString('en-US', { weekday: 'short' })
        });

        currentDate.setDate(currentDate.getDate() + 1);
    }
}

const allDates = ref([]);
const isPickerOpen = ref(false);
const fromDate = ref('');
const toDate = ref('');
const isDialogOpen = ref(false);
const currentData = ref({});

const reservationStore = useReservationStore();
const reservationData = ref([]);

const today = new Date();
const currentMonth = new Date().getMonth();
const currentYear = new Date().getFullYear();


const getData = async (from, till) => {
    reservationData.value = await reservationStore.getReservationsData(from, till);
}

const closeModal = async() => {
    isDialogOpen.value = false;
    currentData.value = {};
    reservationData.value = await reservationStore.getReservationsData(fromDate.value, toDate.value);
}

const openModal = (resData) => {
    console.log(`testing data`);
    console.log(resData);
    currentData.value = resData
    isDialogOpen.value = true;
}

const updateToday = () => {
    fromDate.value = new Date().toISOString().slice(0, 10);
    toDate.value = new Date().toISOString().slice(0, 10);
    getData(fromDate.value, toDate.value);
    getDaysForDateRange(fromDate.value, toDate.value);
}

const prevFunction = () => {
    const currentFrom = new Date(fromDate.value);

    const newDateTill = new Date(currentFrom);
    newDateTill.setDate(currentFrom.getDate() - 1);

    const newDateFrom = new Date(newDateTill);
    newDateFrom.setDate(newDateTill.getDate() - 13);

    fromDate.value = newDateFrom.toISOString().slice(0, 10);
    toDate.value = newDateTill.toISOString().slice(0, 10);

    getData(fromDate.value, toDate.value);
    getDaysForDateRange(fromDate.value, toDate.value);
};


const nextFunction = () => {
    const from = new Date(toDate.value);

    const newDateFrom = new Date(from);
    newDateFrom.setDate(from.getDate() + 1);

    const newDateTill = new Date(from);
    newDateTill.setDate(from.getDate() + 14);

    fromDate.value = newDateFrom.toISOString().slice(0, 10);
    toDate.value = newDateTill.toISOString().slice(0, 10);

    getData(fromDate.value, toDate.value);
    getDaysForDateRange(fromDate.value, toDate.value);
};


onMounted( () => {
    const { from_date, till_date } = getDefaultReservationRange(13);
    fromDate.value = from_date;
    toDate.value = till_date;
    getData(fromDate.value, toDate.value);
    getDaysForDateRange(fromDate.value, toDate.value);
})
</script>
