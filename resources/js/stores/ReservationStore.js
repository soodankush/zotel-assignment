import { defineStore } from 'pinia';
import axios from 'axios';
export const useReservationStore = defineStore('reservation', {
    state: () => ({
        reservationData: {},
    }),
    actions: {
        async getReservationsData(from_date, till_date) {
            try{
                const responseData = await axios.get(`http://localhost:8000/api/data/${from_date}/${till_date}`);
                if(responseData.status === 200) {
                    this.reservationData = responseData.data;
                    return this.reservationData;
                } else {
                    alert('Error fetching data')
                }
            } catch (e) {
                console.log(e);
                alert('Something went wrong');
            }

        },
    }
})
