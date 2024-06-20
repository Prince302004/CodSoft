import tkinter as tk
from tkinter import messagebox

class ToDoListApp:
    def __init__(self, root):
        self.root = root
        self.root.title("To-Do List")

        self.tasks = []

        self.frame = tk.Frame(self.root)
        self.frame.pack(pady=10)

        self.listbox = tk.Listbox(
            self.frame, width=50, height=10, bd=0, selectmode=tk.SINGLE
        )
        self.listbox.pack(side=tk.LEFT, fill=tk.BOTH)

        self.scrollbar = tk.Scrollbar(self.frame)
        self.scrollbar.pack(side=tk.RIGHT, fill=tk.BOTH)

        self.listbox.config(yscrollcommand=self.scrollbar.set)
        self.scrollbar.config(command=self.listbox.yview)

        self.entry = tk.Entry(self.root, width=50)
        self.entry.pack(pady=10)

        self.button_frame = tk.Frame(self.root)
        self.button_frame.pack(pady=10)

        self.add_task_button = tk.Button(
            self.button_frame, text="Add Task", command=self.add_task
        )
        self.add_task_button.pack(side=tk.LEFT, padx=5)

        self.remove_task_button = tk.Button(
            self.button_frame, text="Remove Task", command=self.remove_task
        )
        self.remove_task_button.pack(side=tk.LEFT, padx=5)

        self.view_tasks_button = tk.Button(
            self.button_frame, text="View Tasks", command=self.view_tasks
        )
        self.view_tasks_button.pack(side=tk.LEFT, padx=5)

    def add_task(self):
        task = self.entry.get()
        if task != "":
            self.tasks.append(task)
            self.update_tasks()
            self.entry.delete(0, tk.END)
        else:
            messagebox.showwarning("Warning", "You must enter a task.")

    def remove_task(self):
        selected_task_index = self.listbox.curselection()
        if selected_task_index:
            task = self.tasks.pop(selected_task_index[0])
            self.update_tasks()
            messagebox.showinfo("Info", f"Task '{task}' removed.")
        else:
            messagebox.showwarning("Warning", "You must select a task.")

    def view_tasks(self):
        if not self.tasks:
            messagebox.showinfo("Info", "Your to-do list is empty.")
        else:
            tasks_str = "\n".join(self.tasks)
            messagebox.showinfo("Your To-Do List", tasks_str)

    def update_tasks(self):
        self.listbox.delete(0, tk.END)
        for task in self.tasks:
            self.listbox.insert(tk.END, task)


if __name__ == "__main__":
    root = tk.Tk()
    app = ToDoListApp(root)
    root.mainloop()