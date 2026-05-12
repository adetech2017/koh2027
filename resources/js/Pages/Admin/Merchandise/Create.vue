<template>
  <AdminLayout>
    <div class="max-w-2xl">
      <div class="mb-6">
        <Link href="/admin/merchandise" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Merchandise
        </Link>
        <h2 class="text-2xl font-bold text-dark">Add Merchandise Item</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Product Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Product name"
            />
            <p v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name }}</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="4"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Product description"
            />
            <p v-if="errors.description" class="text-red-600 text-sm mt-1">{{ errors.description }}</p>
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Category</label>
            <input
              v-model="form.category"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="e.g., Clothing, Accessories"
            />
            <p v-if="errors.category" class="text-red-600 text-sm mt-1">{{ errors.category }}</p>
          </div>

          <!-- SKU -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">SKU</label>
            <input
              v-model="form.sku"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Unique SKU"
            />
            <p v-if="errors.sku" class="text-red-600 text-sm mt-1">{{ errors.sku }}</p>
          </div>

          <!-- Price and Currency -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Price</label>
              <input
                v-model.number="form.price"
                type="number"
                step="0.01"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="0.00"
              />
              <p v-if="errors.price" class="text-red-600 text-sm mt-1">{{ errors.price }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-dark mb-2">Currency</label>
              <select
                v-model="form.currency"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Select Currency</option>
                <option value="NGN">NGN (₦)</option>
                <option value="USD">USD ($)</option>
              </select>
              <p v-if="errors.currency" class="text-red-600 text-sm mt-1">{{ errors.currency }}</p>
            </div>
          </div>

          <!-- Stock Quantity -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Stock Quantity</label>
            <input
              v-model.number="form.stock_quantity"
              type="number"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="0"
              min="0"
            />
            <p v-if="errors.stock_quantity" class="text-red-600 text-sm mt-1">{{ errors.stock_quantity }}</p>
          </div>

          <!-- Sizes (JSON) -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Sizes (JSON)</label>
            <textarea
              v-model="form.sizes"
              rows="2"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm"
              placeholder='["S", "M", "L", "XL"]'
            />
            <p v-if="errors.sizes" class="text-red-600 text-sm mt-1">{{ errors.sizes }}</p>
          </div>

          <!-- Colors (JSON) -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Colors (JSON)</label>
            <textarea
              v-model="form.colors"
              rows="2"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary font-mono text-sm"
              placeholder='["Red", "Blue", "Black"]'
            />
            <p v-if="errors.colors" class="text-red-600 text-sm mt-1">{{ errors.colors }}</p>
          </div>

          <!-- Sort Order -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Sort Order</label>
            <input
              v-model.number="form.sort_order"
              type="number"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="0"
            />
            <p v-if="errors.sort_order" class="text-red-600 text-sm mt-1">{{ errors.sort_order }}</p>
          </div>

          <!-- Checkboxes -->
          <div class="space-y-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.in_stock" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">In Stock</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Active</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Featured</span>
            </label>
          </div>

          <!-- Buttons -->
          <div class="flex gap-4 pt-6">
            <button
              type="submit"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
            >
              Create Product
            </button>
            <Link
              href="/admin/merchandise"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium transition-colors"
            >
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const errors = ref({})

const form = reactive({
  name: '',
  description: '',
  category: '',
  price: '',
  currency: 'NGN',
  sizes: '[]',
  colors: '[]',
  sku: '',
  stock_quantity: 0,
  in_stock: true,
  is_active: true,
  is_featured: false,
  sort_order: 0,
})

const submitForm = () => {
  errors.value = {}
  router.post('/admin/merchandise', form, {
    onError: (err) => {
      errors.value = err
    },
  })
}
</script>
