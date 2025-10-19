<script setup lang="ts">
import { ref } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Sun, Moon, Settings2 } from 'lucide-vue-next'

const open = ref(false)
const { theme, color, radius } = useTheme()

// const colors = [
//   'zinc', 'slate', 'stone', 'gray', 'neutral',
//   'red', 'rose', 'orange', 'yellow', 'green', 'blue', 'violet'
// ]
// const radii = [0, 0.25, 0.5, 0.75, 1]
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        size="icon"
        variant="outline"
        class="fixed bottom-6 right-6 rounded-full shadow-lg backdrop-blur-md bg-background/90 dark:bg-muted transition-transform hover:scale-105"
      >
        <Settings2 class="h-5 w-5" />
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-72 p-4 space-y-4">
      <h3 class="font-semibold text-base">Theme Customizer</h3>
      <!-- <p class="text-sm text-muted-foreground">Customize your theme style.</p> -->

      <!-- Color -->
      <!-- <div>
        <h4 class="text-sm font-medium mb-2">Color</h4>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="c in colors"
            :key="c"
            @click="color = c"
            class="h-8 w-8 rounded-full border transition-all hover:scale-105 active:scale-95"
            :class="[
              color === c ? 'ring-2 ring-offset-2 ring-primary' : '',
              {
                'bg-zinc-500': c === 'zinc',
                'bg-slate-500': c === 'slate',
                'bg-stone-500': c === 'stone',
                'bg-gray-500': c === 'gray',
                'bg-neutral-500': c === 'neutral',
                'bg-red-500': c === 'red',
                'bg-rose-500': c === 'rose',
                'bg-orange-500': c === 'orange',
                'bg-yellow-500': c === 'yellow',
                'bg-green-500': c === 'green',
                'bg-blue-500': c === 'blue',
                'bg-violet-500': c === 'violet',
              }
            ]"
          ></button>
        </div>
      </div> -->

      <!-- Radius -->
      <!-- <div>
        <h4 class="text-sm font-medium mb-2">Radius</h4>
        <div class="flex gap-2">
          <button
            v-for="r in radii"
            :key="r"
            @click="radius = r"
            class="px-2 py-1 rounded-md border text-xs"
            :class="radius === r ? 'bg-primary text-primary-foreground' : ''"
          >
            {{ r }}
          </button>
        </div>
      </div> -->

      <!-- Theme -->
      <div>
        <!-- <h4 class="text-sm font-medium mb-2">Theme</h4> -->
        <div class="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            :class="{ 'border-primary text-primary': theme === 'light' }"
            @click="theme = 'light'"
          >
            <Sun class="h-4 w-4 mr-1" /> Light
          </Button>
          <Button
            variant="outline"
            size="sm"
            :class="{ 'border-primary text-primary': theme === 'dark' }"
            @click="theme = 'dark'"
          >
            <Moon class="h-4 w-4 mr-1" /> Dark
          </Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
