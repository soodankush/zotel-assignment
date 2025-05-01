<template>
    <div id="myModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-start pt-10">
    <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-lg p-6 w-2/3 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between border-b-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-2xl font-semibold mb-2">Reservation Title</h2>
                </div>

                <div class="flex items-center gap-2">
                    <button @click.stop="closeModal" class="text-gray-300 px-4 pb-6 rounded mr-2 hover:text-gray-600 text-2xl">x</button>
                </div>
            </div>
            <div class="p-6 max-w-5xl mx-auto mt-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <div>
                        <h2 class="text-lg font-semibold mb-4 border-b pb-2">Transaction Details</h2>
                        <p><span class="font-semibold">Transaction Id:</span> {{ props.reservationData.transaction_id }}</p>
                        <p><span class="font-semibold">Customer Name:</span> {{ props.reservationData.customer_name }}</p>
                        <p><span class="font-semibold">Check-In:</span> {{ props.reservationData.check_in }}</p>
                        <p><span class="font-semibold">Check-Out:</span> {{ props.reservationData.check_out }}</p>
                        <p><span class="font-semibold">Total Amount:</span> {{ props.reservationData.total_amount }}</p>
                        <p><span class="font-semibold">Paid:</span> 0.00</p>
                        <p><span class="font-semibold">Due:</span> {{ props.reservationData.total_amount }}</p>
                    </div>

                    <!-- Room Details -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4 border-b pb-2">Room Details</h2>
                        <p @dblclick="fetchAvailableRooms">
                            <span class="font-semibold">All Rooms:</span>

                            <template v-if="editing">
                                <select v-model="selectedRoom" class="ml-2 border px-2 py-1 rounded">
                                    <option v-for="room in availableRooms" :key="room" :value="room">
                                        {{ room }}
                                    </option>
                                </select>
                                <button
                                    class="ml-2 px-2 py-1 text-white bg-green-600 rounded hover:bg-green-700"
                                    @click="updateRoom"
                                >
                                    Save
                                </button>
                            </template>

                            <template v-else>
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded">{{ props.reservationData.rooms }}</span>
                            </template>
                        </p>
                        <p><span class="font-semibold">Transaction Status:</span> {{ props.reservationData.transaction_status }}</p>
                        <p><span class="font-semibold">Guest:</span> {{ props.reservationData.customer_name }}</p>
                        <p><span class="font-semibold">Guest Email:</span> {{ props.reservationData.customer_email }}</p>
                        <p><span class="font-semibold">Guest Phone:</span> +91 78924 41267</p>
                    </div>

                </div>

                <!-- Action Buttons -->
                <div class="mt-8">
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Check In</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Check Out</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Send Email</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Send WhatsApp</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Add Charge</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Add Payment</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2" @click="reallocateRoom">Re-Allocate Room</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Release Room</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Add Guest</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">Create Invoice</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2" @click="toggleCalendar">Edit Reservation</button>
                    <button class="border px-4 py-2 rounded hover:bg-gray-100 m-2">No Show</button>
                </div>
            </div>
            <DateCalendar
              :id=props.reservationData.id
              :reservationFrom="props.reservationData.check_in"
              :reservationTill="props.reservationData.check_out"
              @toggleCalendar="toggleCalendar"
            />
        </div>
    </div>
</template>

<script setup>
import DateCalendar from "./DateCalendar.vue";
import { ref, defineProps, defineEmits } from 'vue';

const emit = defineEmits(['close']);

const props = defineProps({
    reservationData: {
        type: Object,
        required: true,
    }
});

const editing = ref(false);
const availableRooms = ref([]);
const selectedRoom = ref(null);

const openModal = () => {
    document.getElementById('myModal').classList.remove('hidden');
}

const closeModal = () => {
    emit('close');
}

const fetchAvailableRooms = async () => {
    try {
        const res = await axios.get(`/api/available-rooms/${props.reservationData.id}`);

        if (res.status === 200) {
            availableRooms.value = res.data.available_rooms;
            selectedRoom.value = props.reservationData.rooms;
            editing.value = true;
        } else {
            alert(res.data.error || 'Failed to load available rooms.');
        }
    } catch (err) {
        console.error(err);
        alert(err.response?.data?.error || 'Something went wrong.');
    }
};


const updateRoom = async () => {
    try {
        const res = await axios.post(`/api/update-room/${props.reservationData.id}`, {
            room_no: selectedRoom.value
        });

        if (res.status === 200) {
            alert('Room updated successfully!');
            props.reservationData.rooms = selectedRoom.value;
            editing.value = false;
        } else {
            alert(res.data.error || 'Failed to update room.');
        }
    } catch (err) {
        console.error(err);
        alert(err.response?.data?.error || 'Something went wrong while updating.');
    }
};


const toggleCalendar = (updatedData) => {
    if (updatedData) {
        props.reservationData.check_in = updatedData.check_in;
        props.reservationData.check_out = updatedData.check_out;
    }
    const calendar = document.getElementById('calendar');
    calendar.classList.toggle('hidden');
}

const reallocateRoom = () => {
    alert("rooms will be reallocated");
}
</script>

<style scoped>

</style>
