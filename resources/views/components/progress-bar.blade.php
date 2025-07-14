<div
    x-data="{ progress: 0, isComplete: false }"
    x-init="() => {
        $watch('progress', value => {
            if (value >= 100) {
                isComplete = true;
            }
        });
    }"
    class="w-full"
>
    <div class="relative pt-1">
        <div class="flex mb-2 items-center justify-between">
            <div>
                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full" :class="isComplete ? 'text-green-600 bg-green-200 dark:text-green-400 dark:bg-green-900' : 'text-indigo-600 bg-indigo-200 dark:text-indigo-400 dark:bg-indigo-900'">
                    <span x-text="isComplete ? 'Scan Complete' : 'Scanning'"></span>
                </span>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold inline-block" :class="isComplete ? 'text-green-600 dark:text-green-400' : 'text-indigo-600 dark:text-indigo-400'">
                    <span x-text="progress"></span>%
                </span>
            </div>
        </div>
        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-indigo-200 dark:bg-indigo-900">
            <div
                :style="'width: ' + progress + '%'"
                :class="isComplete ? 'bg-green-500' : 'bg-indigo-500'"
                class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500"
            ></div>
        </div>
    </div>
</div> 