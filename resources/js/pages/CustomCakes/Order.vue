<script setup>
import { onMounted, ref } from "vue";
import useAxios from "@/composables/useAxios";
import { useToast } from "vue-toastification"; // For toast notifications

const data = ref([]);
const isLoading = ref(true); // Track loading state
const toast = useToast(); // Initialize toast for notifications

const { loading, error, sendRequest } = useAxios();

const getData = async () => {
  try {
    const response = await sendRequest({
      url: "/v1/custom-cake-orders",
      method: "GET",
    });
    data.value = response.data;
  } catch (err) {
    toast.error("Failed to load custom cake orders");
  } finally {
    isLoading.value = false; // Set loading to false once data is fetched
  }
};

// Function to download file
const downloadFile = async (fileName) => {
  try {
    const response = await sendRequest({
      method: "get",
      url: `/download/${fileName}`, // URL of the backend download route
      responseType: "blob", // Ensure the response is treated as a file
    });

    // Create a link element to trigger download
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", fileName); // Filename for download
    document.body.appendChild(link);
    link.click();

    // Cleanup the link element
    link.remove();
    toast.success("File downloaded successfully!");
  } catch (error) {
    toast.error("Failed to download the file.");
    console.error("Error downloading file:", error.response?.data);
  }
};

onMounted(async () => await getData());
</script>

<template>
  <AppLayout>
    <div class="max-w-6xl mx-auto p-8 bg-gray-50 rounded-lg shadow-lg my-10">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Custom Cake Orders</h2>
      </div>

      <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table
          class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400"
        >
          <thead
            class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
          >
            <tr>
              <th scope="col" class="px-6 py-3">Cake name</th>
              <th scope="col" class="px-6 py-3">Customer Name</th>
              <th scope="col" class="px-6 py-3">Mobile</th>
              <th scope="col" class="px-6 py-3">Address</th>
              <th scope="col" class="px-6 py-3">Flavor</th>
              <th scope="col" class="px-6 py-3">Message On Cake</th>
              <th scope="col" class="px-6 py-3">Weight</th>
              <th scope="col" class="px-6 py-3">Delivery Location</th>
              <th scope="col" class="px-6 py-3">Delivery Date</th>
              <th scope="col" class="px-6 py-3">Action</th>
            </tr>
          </thead>
          <tbody>
            <!-- Skeleton Loader for loading state -->
            <template v-if="isLoading">
              <tr v-for="n in 5" :key="n" class="animate-pulse">
                <td colspan="10" class="px-6 py-4">
                  <div class="h-8 bg-gray-300 rounded w-full"></div>
                </td>
              </tr>
            </template>

            <!-- Actual Data when loading is done -->
            <template v-else>
              <tr
                v-for="(item, index) in data"
                :key="index"
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700"
              >
                <th
                  scope="row"
                  class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                >
                  Cake Name
                </th>
                <td class="px-6 py-4">
                  {{ item.custom_cake_customer.full_name }}
                </td>
                <td class="px-6 py-4">
                  {{ item.custom_cake_customer.phone_number }}
                </td>
                <td class="px-6 py-4">
                  {{ item.custom_cake_customer.address }}
                </td>
                <td class="px-6 py-4">{{ item.custom_cake_flavor.name }}</td>
                <td class="px-6 py-4">{{ item.message_on_cake }}</td>
                <td class="px-6 py-4">{{ item.weight }}kg</td>
                <td class="px-6 py-4 capitalize">
                  {{ item.delivery_location }} Dhaka
                </td>
                <td class="px-6 py-4">
                  {{
                    new Date(item.delivery_date).toLocaleDateString("en-GB", {
                      day: "numeric",
                      month: "short",
                      year: "numeric",
                    })
                  }}
                </td>

                <td class="px-6 py-4">
                  <button
                    class="text-green-500 hover:text-green-700"
                    @click="downloadFile(item?.photo_on_cake)"
                  >
                    <i class="fa fa-download" aria-hidden="true"></i> Download
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
