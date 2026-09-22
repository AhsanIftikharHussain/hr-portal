<div class="flex flex-col gap-5">
    <x-form-input label="Name" name="name" :value="$category->name" maxlength="100" required />
    <x-form-textarea label="Description" name="description" :value="$category->description" maxlength="2000" rows="4" />
</div>
