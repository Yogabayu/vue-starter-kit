<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import * as icons from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'

const { modelValue } = defineProps<{ modelValue: string }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const search = ref('')
const open = ref(false)
const activeIndex = ref(0)


function toKebabFromKey(key: string) {
  return key
    
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    
    .replace(/([0-9])([A-Za-z])/g, '$1-$2')
    .replace(/([A-Za-z])([0-9])/g, '$1-$2')
    .toLowerCase()
}


const validKeys = Object.keys(icons).filter((key) =>
  /^[A-Z]/.test(key) && key !== 'Icon' && !key.startsWith('Lucide') && !key.endsWith('Icon')
)

const kebabToKey = new Map<string, string>()
for (const k of validKeys) {
  const slug = toKebabFromKey(k)
  
  if (!kebabToKey.has(slug)) kebabToKey.set(slug, k)
}


const allIcons = Array.from(kebabToKey.keys()).sort()

const filteredIcons = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = q ? allIcons.filter((name) => name.includes(q)) : allIcons
  return list
})

const suggestions = computed(() => filteredIcons.value.slice(0, 12))

function toPascal(name: string) {
  if (!name) return ''
  return name
    .split('-')
    .map((n) => n.charAt(0).toUpperCase() + n.slice(1))
    .join('')
}


function getIcon(name: string) {
  const key = kebabToKey.get(name) || toPascal(name)
  if (!key || key === 'Icon' || key.startsWith('Lucide') || key.endsWith('Icon')) return null
  return (icons as any)[key] ?? null
}

function selectIcon(name: string) {
  search.value = name
  emit('update:modelValue', name)
  open.value = false
}


const previewName = computed(() => {
  if (getIcon(search.value)) return search.value
  return suggestions.value[activeIndex.value] || ''
})

function onKeydown(e: KeyboardEvent) {
  if (!open.value) return
  const max = suggestions.value.length
  if (!max) return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    activeIndex.value = (activeIndex.value + 1) % max
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    activeIndex.value = (activeIndex.value - 1 + max) % max
  } else if (e.key === 'Enter') {
    e.preventDefault()
    const name = suggestions.value[activeIndex.value]
    if (name) selectIcon(name)
  } else if (e.key === 'Escape') {
    open.value = false
  }
}

function handleBlur() {
  
  window.setTimeout(() => (open.value = false), 100)
}


onMounted(() => {
  search.value = modelValue || ''
})

watch(
  () => modelValue,
  (val) => {
    if (val !== search.value) search.value = val || ''
  }
)

watch(
  () => search.value,
  () => {
    
    activeIndex.value = 0
    open.value = true
  }
)
</script>

<template>
  <div class="space-y-2">
    <!-- Input + Live Preview -->
    <div class="relative">
      <div class="flex items-center gap-2">
        <Input
          v-model="search"
          placeholder="Type to search Lucide icons..."
          @focus="open = true"
          @blur="handleBlur"
          @keydown="onKeydown"
        />
        <component
          v-if="getIcon(previewName)"
          :is="getIcon(previewName)"
          class="h-6 w-6 text-muted-foreground"
        />
      </div>

      <!-- Suggestions Dropdown -->
      <div
        v-show="open && suggestions.length"
        class="absolute z-50 mt-2 w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
        @mousedown.prevent
      >
        <ScrollArea class="max-h-56">
          <ul class="p-1">
            <li
              v-for="(name, idx) in suggestions"
              :key="name"
              class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
              :class="{ 'bg-accent text-accent-foreground': idx === activeIndex }"
              @mouseenter="activeIndex = idx"
              @click="selectIcon(name)"
            >
              <component
                v-if="getIcon(name)"
                :is="getIcon(name)"
                class="h-4 w-4"
              />
              <span class="truncate">{{ name }}</span>
            </li>
          </ul>
        </ScrollArea>
      </div>
    </div>

    <!-- Quick browse grid (optional) -->
    <ScrollArea class="h-48 rounded-md border p-2">
      <div class="grid grid-cols-6 gap-3">
        <div
          v-for="iconName in filteredIcons"
          :key="iconName"
          class="flex flex-col items-center justify-center cursor-pointer rounded-md p-2 text-center hover:bg-accent hover:text-accent-foreground transition"
          :class="{ 'bg-primary text-primary-foreground': iconName === modelValue }"
          @click="selectIcon(iconName)"
        >
          <component
            v-if="getIcon(iconName)"
            :is="getIcon(iconName)"
            class="h-5 w-5 mb-1"
          />
          <span v-else class="text-xs opacity-50">?</span>
          <span class="text-[10px] truncate w-full">{{ iconName }}</span>
        </div>
      </div>
    </ScrollArea>
  </div>
</template>
